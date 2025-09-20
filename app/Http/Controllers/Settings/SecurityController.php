<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\UserTrustedDevice;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class SecurityController extends Controller
{
    public function __construct(private TwoFactorService $service)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $devices = UserTrustedDevice::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->orderByDesc('last_used_at')
            ->get()
            ->map(function ($d) use ($request) {
                $ua = $d->user_agent ?? '';
                $os = null;
                $browser = null;
                if (stripos($ua, 'windows') !== false)
                    $os = 'Windows';
                elseif (stripos($ua, 'mac os') !== false || stripos($ua, 'macintosh') !== false)
                    $os = 'macOS';
                elseif (stripos($ua, 'linux') !== false)
                    $os = 'Linux';
                elseif (stripos($ua, 'iphone') !== false)
                    $os = 'iOS';
                elseif (stripos($ua, 'android') !== false)
                    $os = 'Android';
                if (stripos($ua, 'chrome') !== false && stripos($ua, 'edg') === false)
                    $browser = 'Chrome';
                elseif (stripos($ua, 'safari') !== false && stripos($ua, 'chrome') === false)
                    $browser = 'Safari';
                elseif (stripos($ua, 'firefox') !== false)
                    $browser = 'Firefox';
                elseif (stripos($ua, 'edg') !== false)
                    $browser = 'Edge';
                return [
                    'id' => $d->id,
                    'name' => $d->device_label,
                    'last_used_at' => $d->last_used_at?->toDateTimeString(),
                    'created_at' => $d->created_at->toDateTimeString(),
                    'ip_address' => $d->ip_address,
                    'os' => $d->client_os ?? $os,
                    'browser' => $d->client_browser ?? $browser,
                    'user_agent' => $ua ? substr($ua, 0, 180) : null,
                    'current' => false, // set after fingerprint if desired
                ];
            });

        $currentId = null;
        // Attempt to resolve current device via cookies (best-effort)
        $deviceIdCookie = $request->cookies->get('device_id');
        $trustedToken = $request->cookies->get('trusted_device_token');
        if ($deviceIdCookie && $trustedToken) {
            // Rebuild fingerprint to locate device row
            $fingerprint = $this->service->generateFingerprint(
                $deviceIdCookie,
                (string) $request->userAgent(),
                (string) ($request->header('Sec-CH-UA-Platform') ?? '')
            );
            $hash = hash('sha256', $trustedToken);
            $match = UserTrustedDevice::where('user_id', $user->id)
                ->where('fingerprint_hash', $fingerprint)
                ->where('token_hash', $hash)
                ->whereNull('revoked_at')
                ->first();
            if ($match) {
                $currentId = $match->id;
            }
        }

        $devices = $devices->map(function ($d) use ($currentId) {
            if ($d['id'] === $currentId) {
                $d['current'] = true;
            }
            return $d;
        });

        return Inertia::render('settings/Security', [
            'two_factor_mode' => $user->two_factor_mode,
            'devices' => $devices,
            'current_device_id' => $currentId,
        ]);
    }

    public function enableTwoFactor(Request $request)
    {
        $request->validate(['password' => ['required', 'string']]);
        $user = $request->user();
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Password does not match']);
        }
        if ($user->two_factor_mode !== 'email_on_new_device') {
            $user->forceFill([
                'two_factor_mode' => 'email_on_new_device',
                'two_factor_email_enabled_at' => now(),
            ])->save();
            // Clear any skip/passed flags so a fresh challenge is enforced
            $request->session()->forget('2fa.passed_at');
            $request->session()->forget('2fa.skip_until');
        }
        return back();
    }

    public function disableTwoFactor(Request $request)
    {
        $request->validate(['password' => ['required', 'string']]);
        $user = $request->user();
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Password does not match']);
        }
        if ($user->two_factor_mode !== 'disabled') {
            $user->forceFill(['two_factor_mode' => 'disabled'])->save();
            $request->session()->forget('2fa.passed_at');
            $request->session()->forget('2fa.skip_until');
        }
        return back();
    }

    public function destroyDevice(Request $request, UserTrustedDevice $device)
    {
        $this->authorize('update', $request->user()); // simple ownership gate; can refine
        if ($device->user_id === $request->user()->id) {
            $device->update(['revoked_at' => now()]);
            // Removing current trusted device should force re-challenge next request
            $request->session()->forget('2fa.passed_at');
            \Log::info('trusted_device.revoked', [
                'user_id' => $request->user()->id,
                'device_id' => $device->id,
            ]);
        }
        return back();
    }

    public function destroyAllDevices(Request $request)
    {
        UserTrustedDevice::where('user_id', $request->user()->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
        // Force fresh challenge next navigation
        $request->session()->forget('2fa.passed_at');
        $request->session()->forget('2fa.skip_until');
        return back();
    }

    public function renameDevice(Request $request, UserTrustedDevice $device)
    {
        $request->validate(['name' => ['nullable', 'string', 'max:100']]);
        if ($device->user_id !== $request->user()->id) {
            abort(403);
        }
        $device->update(['device_label' => $request->input('name') ?: null]);
        \Log::info('trusted_device.renamed', [
            'user_id' => $request->user()->id,
            'device_id' => $device->id,
            'new_label' => $device->device_label,
        ]);
        return back();
    }

    public function logoutOthers(Request $request)
    {
        $request->validate(['password' => ['required', 'string']]);
        $user = $request->user();
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Password does not match']);
        }
        Auth::logoutOtherDevices($request->password);
        return back();
    }
}

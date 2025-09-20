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

        // Fallback: if no currentId resolved via cookies but session indicates a recent pass, pick latest device
        if (!$currentId && $request->session()->has('2fa.passed_at')) {
            $recent = UserTrustedDevice::where('user_id', $user->id)
                ->whereNull('revoked_at')
                ->orderByDesc('last_used_at')
                ->first();
            if ($recent) {
                $currentId = $recent->id;
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
        if ($user->two_factor_mode === 'email_on_new_device') {
            return back(); // already enabled
        }
        // Defer enabling until after successful 2FA challenge
        $this->queueForcedChallenge($request, [
            'type' => 'enable_2fa',
        ]);
        return redirect()->route('2fa.challenge');
    }

    public function disableTwoFactor(Request $request)
    {
        $request->validate(['password' => ['required', 'string']]);
        $user = $request->user();
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Password does not match']);
        }
        if ($user->two_factor_mode === 'disabled') {
            return back(); // already disabled
        }
        $this->queueForcedChallenge($request, [
            'type' => 'disable_2fa',
        ]);
        return redirect()->route('2fa.challenge');
    }

    public function destroyDevice(Request $request, UserTrustedDevice $device)
    {
        if ($device->user_id !== $request->user()->id)
            abort(403);
        $this->queueForcedChallenge($request, [
            'type' => 'revoke_one',
            'id' => $device->id,
        ]);
        return redirect()->route('2fa.challenge');
    }

    public function destroyAllDevices(Request $request)
    {
        $this->queueForcedChallenge($request, [
            'type' => 'revoke_all',
        ]);
        return redirect()->route('2fa.challenge');
    }

    public function renameDevice(Request $request, UserTrustedDevice $device)
    {
        $request->validate(['name' => ['nullable', 'string', 'max:100']]);
        if ($device->user_id !== $request->user()->id)
            abort(403);
        $this->queueForcedChallenge($request, [
            'type' => 'rename',
            'id' => $device->id,
            'name' => $request->input('name'),
        ]);
        return redirect()->route('2fa.challenge');
    }

    /**
     * Queue a forced challenge and store pending action details.
     */
    protected function queueForcedChallenge(Request $request, array $pending): void
    {
        $request->session()->put('2fa.pending_action', $pending);
        $request->session()->put('2fa.forced', true);
        $request->session()->forget('2fa.skip_until');
        // Do NOT clear passed_at; keeping it avoids unnecessary re-challenges while action pending

        // Ensure a fingerprint & challenge exist (if not already issued in this session)
        if (!$request->session()->has('2fa.fingerprint')) {
            $deviceId = $request->cookies->get('device_id');
            if (!$deviceId) {
                $deviceId = bin2hex(random_bytes(16));
                cookie()->queue(cookie('device_id', $deviceId, 60 * 24 * 365));
            }
            $fingerprint = $this->service->generateFingerprint(
                $deviceId,
                (string) $request->userAgent(),
                (string) ($request->header('Sec-CH-UA-Platform') ?? '')
            );
            $this->service->issueChallenge($request->user(), $fingerprint);
            $request->session()->put('2fa.fingerprint', $fingerprint);
            if (!$request->session()->has('url.intended')) {
                $request->session()->put('url.intended', $request->fullUrl());
            }
        }
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

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
                return [
                    'id' => $d->id,
                    'name' => $d->device_label,
                    'last_used_at' => $d->last_used_at?->toDateTimeString(),
                    'created_at' => $d->created_at->toDateTimeString(),
                    'current' => false, // set after fingerprint if desired
                ];
            });

        return Inertia::render('settings/Security', [
            'two_factor_mode' => $user->two_factor_mode,
            'devices' => $devices,
            'current_device_id' => null,
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
        }
        return back();
    }

    public function destroyDevice(Request $request, UserTrustedDevice $device)
    {
        $this->authorize('update', $request->user()); // simple ownership gate; can refine
        if ($device->user_id === $request->user()->id) {
            $device->update(['revoked_at' => now()]);
        }
        return back();
    }

    public function destroyAllDevices(Request $request)
    {
        UserTrustedDevice::where('user_id', $request->user()->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
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

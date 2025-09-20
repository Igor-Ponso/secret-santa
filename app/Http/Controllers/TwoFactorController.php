<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\RateLimiter;

class TwoFactorController extends Controller
{
    public function __construct(private TwoFactorService $service)
    {
    }

    public function challenge(Request $request)
    {
        if (!$request->user())
            return redirect()->route('login');
        if (!$request->session()->has('2fa.fingerprint'))
            return redirect()->route('dashboard');
        return Inertia::render('auth/TwoFactorChallenge', [
            'mode' => $request->user()->two_factor_mode,
            'resent' => session('2fa.resent') ?? false,
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'min:4', 'max:16']
        ]);
        $user = $request->user();
        if (!$user)
            return redirect()->route('login');
        $fingerprint = $request->session()->get('2fa.fingerprint');
        if (!$fingerprint)
            return redirect()->route('dashboard');

        $code = strtoupper(trim($request->input('code')));
        if (!$this->service->validateCode($user, $fingerprint, $code)) {
            return back()->withErrors(['code' => 'Invalid or expired code']);
        }
        // Trust device (if user left checkbox on)
        if ($request->boolean('trust', true)) {
            $device = $this->service->trustDevice($user, $fingerprint);
            cookie()->queue(cookie('trusted_device_token', $device->plain_token, 60 * 24 * 365, httpOnly: true));
        }
        $request->session()->forget('2fa.fingerprint');
        return redirect()->intended(route('dashboard'));
    }

    public function resend(Request $request)
    {
        $user = $request->user();
        if (!$user)
            return redirect()->route('login');
        $fingerprint = $request->session()->get('2fa.fingerprint');
        if (!$fingerprint)
            return redirect()->route('dashboard');
        $this->service->issueChallenge($user, $fingerprint);
        session(['2fa.resent' => true]);
        return back();
    }
}

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
        $fingerprint = $request->session()->get('2fa.fingerprint');
        $challenge = \App\Models\EmailSecondFactorChallenge::where('user_id', $request->user()->id)
            ->where('fingerprint_hash', $fingerprint)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();
        $expiresAt = $challenge?->expires_at;
        $remaining = 0;
        if ($expiresAt) {
            // diffInSeconds with second param false yields signed difference (positive if future)
            $remaining = max(0, now()->diffInSeconds($expiresAt, false));
        }
        return Inertia::render('auth/TwoFactorChallenge', [
            'mode' => $request->user()->two_factor_mode,
            'resent' => session('2fa.resent') ?? false,
            'expires_at' => $expiresAt?->toIso8601String(),
            'remaining_seconds' => $remaining,
            'server_time' => now()->toIso8601String(),
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
            $context = [
                'ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ];
            $device = $this->service->trustDevice($user, $fingerprint, null, $context);
            cookie()->queue(cookie('trusted_device_token', $device->plain_token, 60 * 24 * 365, httpOnly: true));
        }
        $request->session()->forget('2fa.fingerprint');
        $request->session()->put('2fa.passed_at', now()->toIso8601String());
        $request->session()->forget('2fa.skip_until');
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

    public function cancel(Request $request)
    {
        // Clear active challenge state
        $request->session()->forget('2fa.fingerprint');
        $request->session()->forget('2fa.resent');
        $request->session()->forget('url.intended');
        // Provide a short skip window so user can review settings without being re-challenged instantly.
        $skipSeconds = 180; // align with code ttl; configurable later if needed
        $request->session()->put('2fa.skip_until', now()->addSeconds($skipSeconds)->toIso8601String());
        return redirect()->route('security.index');
    }
}

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
        $pending = $request->session()->get('2fa.pending_action');
        [$canResend, $wait, $suspended, $nextResendAt, $resendCount, $maxBeforeSuspend] = $this->service->resendStatus($request->user());
        $minInterval = (int) config('twofactor.min_resend_interval', 5);
        return Inertia::render('auth/TwoFactorChallenge', [
            'mode' => $request->user()->two_factor_mode,
            'resent' => session('2fa.resent') ?? false,
            'expires_at' => $expiresAt?->toIso8601String(),
            'remaining_seconds' => $remaining,
            'server_time' => now()->toIso8601String(),
            'resend_allowed' => $canResend,
            'resend_wait_seconds' => $wait,
            'resend_suspended' => $suspended,
            'resend_min_interval' => $minInterval,
            'next_resend_at' => $nextResendAt,
            'resend_attempt_count' => $resendCount,
            'resend_max_before_suspend' => $maxBeforeSuspend,
            'pending_action' => $pending && is_array($pending) ? [
                'type' => $pending['type'] ?? null,
                'id' => $pending['id'] ?? null,
                'name' => $pending['name'] ?? null,
            ] : null,
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
        $pending = $request->session()->pull('2fa.pending_action');
        if ($pending && is_array($pending)) {
            $action = match ($pending['type'] ?? null) {
                'revoke_all' => function () use ($request) {
                        \App\Models\UserTrustedDevice::where('user_id', $request->user()->id)
                        ->whereNull('revoked_at')
                        ->update(['revoked_at' => now()]);
                    },
                'revoke_one' => function () use ($request, $pending) {
                        if (!empty($pending['id'])) {
                            \App\Models\UserTrustedDevice::where('user_id', $request->user()->id)
                            ->where('id', $pending['id'])
                            ->update(['revoked_at' => now()]);
                        }
                    },
                'rename' => function () use ($request, $pending) {
                        if (!empty($pending['id'])) {
                            \App\Models\UserTrustedDevice::where('user_id', $request->user()->id)
                            ->where('id', $pending['id'])
                            ->update(['device_label' => $pending['name']]);
                        }
                    },
                'enable_2fa' => function () use ($request) {
                        $u = $request->user();
                        if ($u->two_factor_mode !== 'email_on_new_device') {
                            $u->forceFill([
                            'two_factor_mode' => 'email_on_new_device',
                            'two_factor_email_enabled_at' => now(),
                            ])->save();
                        }
                    },
                'disable_2fa' => function () use ($request) {
                        $u = $request->user();
                        if ($u->two_factor_mode !== 'disabled') {
                            $u->forceFill(['two_factor_mode' => 'disabled'])->save();
                        }
                    },
                default => null,
            };
            if ($action instanceof \Closure) {
                $action();
            }
        }
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
        // Fast burst (per-second) limiter using cache independent of DB row to stop scripting
        $burstKey = '2fa:resend:burst:' . $user->id;
        $burst = cache()->increment($burstKey);
        if ($burst === 1) {
            cache()->put($burstKey, 1, 2); // 2s TTL window
        }
        $burstMax = 2; // allow at most 2 resend attempts per 2s window
        if ($burst > $burstMax) {
            return back()->withErrors(['resend' => 'Too many quick requests. Please slow down.']);
        }
        [$allowed, $wait, $suspended] = \DB::transaction(function () use ($user) {
            return $this->service->registerResendAttempt($user);
        });
        if ($suspended) {
            // Trigger password reset email and logout user (optional). Use built-in broker.
            \Password::broker()->sendResetLink(['email' => $user->email]);
            return back()->withErrors(['resend' => config('twofactor.suspension_reason')]);
        }
        if (!$allowed) {
            $safeWait = max(1, (int) ceil($wait));
            return back()->withErrors(['resend' => 'Please wait ' . $safeWait . 's before requesting another code.']);
        }
        $this->service->issueChallenge($user, $fingerprint);
        session(['2fa.resent' => true]);
        return back();
    }

    public function cancel(Request $request)
    {
        $forced = $request->session()->pull('2fa.forced', false);
        $request->session()->forget('2fa.fingerprint');
        $request->session()->forget('2fa.resent');
        $request->session()->forget('url.intended');
        $request->session()->forget('2fa.pending_action'); // discard any pending destructive action
        if (!$forced) {
            $skipSeconds = 180; // align with code ttl
            $request->session()->put('2fa.skip_until', now()->addSeconds($skipSeconds)->toIso8601String());
        }
        return redirect()->route('security.index');
    }
}

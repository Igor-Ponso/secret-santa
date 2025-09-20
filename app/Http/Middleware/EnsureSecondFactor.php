<?php

namespace App\Http\Middleware;

use App\Services\TwoFactorService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSecondFactor
{
    public function __construct(private TwoFactorService $service)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        // Normalize legacy null to 'disabled'
        if ($user->two_factor_mode === null) {
            $user->two_factor_mode = 'disabled';
        }

        // Bypass for already on challenge routes or logout
        if ($request->routeIs('2fa.challenge', '2fa.verify', '2fa.resend') || $request->routeIs('logout')) {
            return $next($request);
        }

        // If user disabled 2FA mode skip
        if (!$this->service->isEmailMode($user)) {
            return $next($request);
        }

        // Build fingerprint components
        $deviceId = $request->cookies->get('device_id');
        if (!$deviceId) {
            $deviceId = bin2hex(random_bytes(16));
            // Queue cookie (httpOnly false ok; no sensitive secret; just identifier) - but sign via framework
            cookie()->queue(cookie('device_id', $deviceId, 60 * 24 * 365));
        }
        $fingerprint = $this->service->generateFingerprint($deviceId, (string) $request->userAgent(), (string) ($request->header('Sec-CH-UA-Platform') ?? ''));

        // First try cookie trusted_device_token validation (if present) to avoid unnecessary DB lookups / challenges
        $trustedToken = $request->cookies->get('trusted_device_token');
        if ($trustedToken && $this->service->validateTrustedToken($user, $fingerprint, $trustedToken)) {
            return $next($request);
        }

        $needs = $this->service->needsChallenge($user, $fingerprint);
        if (!$needs) {
            return $next($request);
        }

        // Issue challenge if none already in session
        if (!$request->session()->has('2fa.fingerprint')) {
            $this->service->issueChallenge($user, $fingerprint);
            $request->session()->put('2fa.fingerprint', $fingerprint);
            // Store intended URL if not already stored to restore after verification
            if (!$request->session()->has('url.intended')) {
                $request->session()->put('url.intended', $request->fullUrl());
            }
        }

        // Redirect to challenge page
        return redirect()->route('2fa.challenge');
    }
}

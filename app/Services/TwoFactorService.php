<?php

namespace App\Services;

use App\Models\EmailSecondFactorChallenge;
use App\Models\User;
use App\Models\UserTrustedDevice;
use App\Models\TwoFactorResendStat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TwoFactorService
{
    /**
     * Map of device id => plain token for current request lifecycle so tests can retrieve it.
     * Not persisted; cleared each request.
     * @var array<int,string>
     */
    protected static array $plainTokens = [];

    public static function getPlainTokenFor(int $deviceId): ?string
    {
        return self::$plainTokens[$deviceId] ?? null;
    }
    public function isEmailMode(User $user): bool
    {
        return $user->two_factor_mode === 'email_on_new_device';
    }

    public function generateFingerprint(string $deviceId, string $userAgent, string $platform): string
    {
        $normalizedUa = substr(strtolower($userAgent), 0, 120);
        $platform = substr(strtolower($platform), 0, 40);
        return hash('sha256', $deviceId . '|' . $normalizedUa . '|' . $platform);
    }

    public function needsChallenge(User $user, string $fingerprintHash): bool
    {
        if (!$this->isEmailMode($user))
            return false;
        $trusted = UserTrustedDevice::where('user_id', $user->id)
            ->where('fingerprint_hash', $fingerprintHash)
            ->whereNull('revoked_at')
            ->first();
        return !$trusted; // no trusted device record -> need challenge
    }

    public function issueChallenge(User $user, string $fingerprintHash): EmailSecondFactorChallenge
    {
        // Invalidate existing pending challenges for same fingerprint
        EmailSecondFactorChallenge::where('user_id', $user->id)
            ->where('fingerprint_hash', $fingerprintHash)
            ->whereNull('consumed_at')
            ->delete();

        $code = $this->generateCode();
        $challenge = EmailSecondFactorChallenge::create([
            'user_id' => $user->id,
            'fingerprint_hash' => $fingerprintHash,
            'code_hash' => hash('sha256', $code),
            'attempts_remaining' => (int) config('twofactor.max_attempts', 5),
            'expires_at' => now()->addSeconds((int) config('twofactor.code_ttl', 300)),
        ]);

        // Expose plain code for automated tests (never in non-testing env)
        if (app()->environment('testing')) {
            session(['2fa.last_code' => $code]);
        }

        // Dispatch email (queue or send based on config)
        $mailable = new \App\Mail\TwoFactorCodeMail($user, $code);
        if (config('twofactor.use_queue')) {
            Mail::to($user->email)->queue($mailable);
        } else {
            Mail::to($user->email)->send($mailable);
        }

        // Optional debug log (can be toggled later by env)
        if (config('app.debug')) {
            \Log::info('2FA challenge issued', [
                'user_id' => $user->id,
                'fingerprint' => substr($fingerprintHash, 0, 12) . '...',
                'mode' => $user->two_factor_mode,
            ]);
        }

        return $challenge;
    }

    /**
     * Determine if user can request a resend right now and optionally increment counters.
     * Returns array: [allowed(bool), wait_seconds(int), suspended(bool)]
     */
    public function registerResendAttempt(User $user): array
    {
        $cfg = config('twofactor.resend_backoff', []);
        $maxBeforeSuspend = (int) config('twofactor.max_resends_before_suspend', 7);
        // Lock row to prevent race conditions in concurrent requests
        $stat = TwoFactorResendStat::where('user_id', $user->id)->lockForUpdate()->first();
        if (!$stat) {
            $stat = TwoFactorResendStat::create(['user_id' => $user->id]);
        }

        // Check suspension
        if ($stat->suspended_at) {
            return [false, 0, true];
        }
        // Check timing gate
        if ($stat->next_allowed_resend_at && $stat->next_allowed_resend_at->isFuture()) {
            $wait = max(1, $stat->next_allowed_resend_at->diffInSeconds(now()));
            return [false, $wait, false];
        }
        $minInterval = (int) config('twofactor.min_resend_interval', 5);
        if ($stat->last_resend_at && $stat->last_resend_at->diffInSeconds(now()) < $minInterval) {
            $elapsed = $stat->last_resend_at->diffInSeconds(now());
            $remaining = $minInterval - $elapsed;
            if ($remaining < 0) {
                \Log::warning('2fa.resend.negative_remaining_interval', [
                    'user_id' => $user->id,
                    'elapsed' => $elapsed,
                    'min_interval' => $minInterval,
                ]);
                $remaining = 1;
            }
            return [false, max(1, $remaining), false];
        }

        // Increment attempt
        $stat->resend_count += 1;
        $stat->last_resend_at = now();

        // Determine next backoff or suspension
        if ($stat->resend_count >= $maxBeforeSuspend) {
            $stat->suspended_at = now();
            $stat->next_allowed_resend_at = null;
            $stat->save();
            return [false, 0, true];
        }

        $delay = $cfg[$stat->resend_count] ?? end($cfg) ?: 300; // fallback last or 5m
        $stat->next_allowed_resend_at = now()->addSeconds($delay);
        $stat->save();
        return [true, $delay, false];
    }

    /**
     * Returns structured resend status:
     * [allowed(bool), wait_seconds(int), suspended(bool), next_resend_at(?string ISO8601), resend_count(int), max_before_suspend(int)]
     */
    public function resendStatus(User $user): array
    {
        $stat = TwoFactorResendStat::where('user_id', $user->id)->first();
        $minInterval = (int) config('twofactor.min_resend_interval', 5);
        $maxBeforeSuspend = (int) config('twofactor.max_resends_before_suspend', 7);
        if (!$stat) {
            return [true, 0, false, null, 0, $maxBeforeSuspend];
        }
        if ($stat->suspended_at) {
            return [false, 0, true, null, $stat->resend_count, $maxBeforeSuspend];
        }
        $soonest = null;
        if ($stat->next_allowed_resend_at && $stat->next_allowed_resend_at->isFuture()) {
            $soonest = $stat->next_allowed_resend_at->copy();
        }
        if ($stat->last_resend_at && $stat->last_resend_at->diffInSeconds(now()) < $minInterval) {
            $candidate = $stat->last_resend_at->clone()->addSeconds($minInterval);
            if (!$soonest || $candidate->gt($soonest)) {
                $soonest = $candidate;
            }
        }
        if ($soonest) {
            $wait = $soonest->diffInSeconds(now());
            if ($wait < 0) {
                \Log::warning('2fa.resend.negative_wait_calculated', [
                    'user_id' => $user->id,
                    'soonest' => $soonest->toIso8601String(),
                    'now' => now()->toIso8601String(),
                    'raw_wait' => $wait,
                ]);
                $wait = 1;
            }
            return [false, max(1, $wait), false, $soonest->toIso8601String(), $stat->resend_count, $maxBeforeSuspend];
        }
        return [true, 0, false, null, $stat->resend_count, $maxBeforeSuspend];
    }

    protected function generateCode(): string
    {
        $length = (int) config('twofactor.code_length', 6);
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $max = strlen($alphabet) - 1;
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }
        return $code;
    }

    public function validateCode(User $user, string $fingerprintHash, string $code): bool
    {
        $challenge = EmailSecondFactorChallenge::where('user_id', $user->id)
            ->where('fingerprint_hash', $fingerprintHash)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();
        if (!$challenge)
            return false;
        if ($challenge->expires_at->isPast())
            return false;
        if ($challenge->attempts_remaining <= 0)
            return false;

        $hash = hash('sha256', strtoupper(trim($code)));
        $valid = hash_equals($challenge->code_hash, $hash);
        if ($valid) {
            $challenge->update([
                'consumed_at' => now(),
            ]);
        } else {
            $challenge->decrement('attempts_remaining');
        }
        return $valid;
    }

    public function trustDevice(User $user, string $fingerprintHash, ?string $label = null, array $context = []): UserTrustedDevice
    {
        // Check if device already exists (unique user_id+fingerprint_hash)
        $existing = UserTrustedDevice::where('user_id', $user->id)
            ->where('fingerprint_hash', $fingerprintHash)
            ->first();

        $plain = Str::random(64); // always rotate token when re-trusting
        $hash = hash('sha256', $plain);

        if ($existing) {
            // If it was previously revoked, un-revoke (fresh trust) + rotate token
            $existing->update([
                'device_label' => $label ?? $existing->device_label,
                'user_agent' => $context['user_agent'] ?? $existing->user_agent,
                'ip_address' => $context['ip'] ?? $existing->ip_address,
                'client_name' => $context['client_name'] ?? $existing->client_name,
                'client_os' => $context['client_os'] ?? $existing->client_os,
                'client_browser' => $context['client_browser'] ?? $existing->client_browser,
                'token_hash' => $hash,
                'last_used_at' => now(),
                'revoked_at' => null,
            ]);
            $device = $existing;
            \Log::info('trusted_device.rotated', [
                'user_id' => $user->id,
                'device_id' => $device->id,
            ]);
        } else {
            $device = UserTrustedDevice::create([
                'user_id' => $user->id,
                'fingerprint_hash' => $fingerprintHash,
                'device_label' => $label,
                'user_agent' => $context['user_agent'] ?? null,
                'ip_address' => $context['ip'] ?? null,
                'client_name' => $context['client_name'] ?? null,
                'client_os' => $context['client_os'] ?? null,
                'client_browser' => $context['client_browser'] ?? null,
                'token_hash' => $hash,
                'last_used_at' => now(),
            ]);
            \Log::info('trusted_device.created', [
                'user_id' => $user->id,
                'device_id' => $device->id,
                'ip' => $device->ip_address,
                'ua_snip' => $device->user_agent ? substr($device->user_agent, 0, 40) : null,
            ]);
        }
        // Store plain token in static map for accessor retrieval (no dynamic property usage)
        self::$plainTokens[$device->id] = $plain; // allow later retrieval in same request (tests & cookie issuance)
        return $device;
    }

    public function validateTrustedToken(User $user, string $fingerprintHash, string $plainToken, array $context = []): bool
    {
        $hash = hash('sha256', $plainToken);
        $device = UserTrustedDevice::where('user_id', $user->id)
            ->where('fingerprint_hash', $fingerprintHash)
            ->where('token_hash', $hash)
            ->whereNull('revoked_at')
            ->first();
        if (!$device)
            return false;
        $device->update([
            'last_used_at' => now(),
            'ip_address' => $context['ip'] ?? $device->ip_address,
            'user_agent' => $context['user_agent'] ?? $device->user_agent,
        ]);
        return true;
    }
}

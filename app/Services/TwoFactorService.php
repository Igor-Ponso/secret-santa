<?php

namespace App\Services;

use App\Models\EmailSecondFactorChallenge;
use App\Models\User;
use App\Models\UserTrustedDevice;
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

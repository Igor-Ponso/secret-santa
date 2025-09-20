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

    public function trustDevice(User $user, string $fingerprintHash, ?string $label = null): UserTrustedDevice
    {
        // Generate token (store hashed, return plain for cookie)
        $plain = Str::random(64);
        $hash = hash('sha256', $plain);
        $device = UserTrustedDevice::create([
            'user_id' => $user->id,
            'fingerprint_hash' => $fingerprintHash,
            'device_label' => $label,
            'token_hash' => $hash,
            'last_used_at' => now(),
        ]);
        // Attach plain token for caller to set cookie
        $device->plain_token = $plain; // dynamic property usage
        return $device;
    }

    public function validateTrustedToken(User $user, string $fingerprintHash, string $plainToken): bool
    {
        $hash = hash('sha256', $plainToken);
        $device = UserTrustedDevice::where('user_id', $user->id)
            ->where('fingerprint_hash', $fingerprintHash)
            ->where('token_hash', $hash)
            ->whereNull('revoked_at')
            ->first();
        if (!$device)
            return false;
        $device->update(['last_used_at' => now()]);
        return true;
    }
}

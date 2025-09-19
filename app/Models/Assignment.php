<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $group_id
 * @property int $giver_user_id
 * @property int|null $receiver_user_id (legacy plain column - will be deprecated)
 * @property string|null $receiver_cipher
 */
class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'giver_user_id',
        'receiver_user_id', // still fillable for transitional writes
        'receiver_cipher'
    ];

    protected $hidden = [
        'receiver_cipher'
    ];

    /**
     * Get decrypted receiver user id (prefers cipher, falls back to legacy plain column).
     * Supports versioned cipher format: v<digit>:<base64/serialized cipher json>
     */
    public function getDecryptedReceiverIdAttribute(): ?int
    {
        if ($this->receiver_cipher) {
            try {
                return $this->decryptReceiverCipher($this->receiver_cipher);
            } catch (\Throwable $e) {
                return null; // corrupted or invalid
            }
        }
        return $this->receiver_user_id; // legacy fallback
    }

    /**
     * Encrypt and set receiver using current version prefix.
     */
    public function setEncryptedReceiver(int $receiverUserId): void
    {
        $version = config('encryption.assignments_version', 1);
        $payload = encrypt((string) $receiverUserId);
        // Prefix with version marker for future rotations.
        $this->receiver_cipher = 'v' . $version . ':' . $payload;
        // legacy column intentionally left null
    }

    /**
     * Internal: decrypt versioned (or legacy unversioned) cipher.
     */
    protected function decryptReceiverCipher(string $cipher): int
    {
        if (str_starts_with($cipher, 'v')) {
            $pos = strpos($cipher, ':');
            if ($pos !== false) {
                $version = substr($cipher, 1, $pos - 1); // currently unused but reserved
                $raw = substr($cipher, $pos + 1);
                return (int) decrypt($raw);
            }
        }
        // Fallback: treat as legacy raw cipher without version prefix
        return (int) decrypt($cipher);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function giver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'giver_user_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_user_id');
    }
}

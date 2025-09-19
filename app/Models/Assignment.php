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
     */
    public function getDecryptedReceiverIdAttribute(): ?int
    {
        if ($this->receiver_cipher) {
            try {
                return (int) decrypt($this->receiver_cipher);
            } catch (\Throwable $e) {
                return null; // corrupted or invalid
            }
        }
        return $this->receiver_user_id; // legacy fallback
    }

    /**
     * Convenience to set encrypted receiver (also leaves plain column null if present).
     */
    public function setEncryptedReceiver(int $receiverUserId): void
    {
        $this->receiver_cipher = encrypt((string) $receiverUserId);
        // Optionally null out legacy plain column in future: $this->receiver_user_id = null;
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

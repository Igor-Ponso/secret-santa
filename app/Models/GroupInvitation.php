<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $group_id
 * @property int $inviter_id
 * @property int|null $invited_user_id
 * @property string $email
 * @property string $token   Hashed token (sha256 hex) – never expose plain token after creation.
 * @property \Illuminate\Support\Carbon|null $accepted_at
 * @property \Illuminate\Support\Carbon|null $declined_at
 * @property \Illuminate\Support\Carbon|null $revoked_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 */
class GroupInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'inviter_id',
        'invited_user_id',
        'email',
        'token',
        'accepted_at',
        'declined_at',
        'revoked_at',
        'expires_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'revoked_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public function invitedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function status(): string
    {
        if ($this->revoked_at)
            return 'revoked';
        if ($this->accepted_at)
            return 'accepted';
        if ($this->declined_at)
            return 'declined';
        if ($this->isExpired())
            return 'expired';
        return 'pending';
    }
}

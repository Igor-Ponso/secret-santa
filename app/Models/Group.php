<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Mass assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'min_value',
        'max_value',
        'draw_at'
    ];

    /**
     * Attribute casting rules.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'draw_at' => 'datetime',
    ];

    /**
     * Owner (group creator / administrator).
     *
     * @return BelongsTo<User, Group>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Invitations issued for this group.
     *
     * @return HasMany<GroupInvitation>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(GroupInvitation::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Group model representing a Secret Santa group.
 *
 * The IDE warning you saw (e.g. "Method Group::select() is not defined") comes from
 * static analysis not understanding Eloquent's dynamic forwarding. The @mixin tag
 * below helps tools know that all Builder methods are available statically.
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
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

    /**
     * Wishlist items (all users) associated to this group.
     *
     * @return HasMany<Wishlist>
     */
    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }
}

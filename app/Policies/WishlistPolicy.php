<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wishlist;

class WishlistPolicy
{
    /**
     * Only the owner can update/delete their wishlist item.
     */
    public function update(User $user, Wishlist $wishlist): bool
    {
        return $wishlist->user_id === $user->id;
    }

    public function delete(User $user, Wishlist $wishlist): bool
    {
        return $wishlist->user_id === $user->id;
    }
}

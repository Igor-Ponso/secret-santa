<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    /**
     * Determine if the user can view the group.
     */
    public function view(User $user, Group $group): bool
    {
        return $group->owner_id === $user->id;
    }

    /**
     * Determine if the user can update the group.
     */
    public function update(User $user, Group $group): bool
    {
        return $group->owner_id === $user->id;
    }

    /**
     * Determine if the user can soft delete the group.
     */
    public function delete(User $user, Group $group): bool
    {
        return $group->owner_id === $user->id;
    }
}

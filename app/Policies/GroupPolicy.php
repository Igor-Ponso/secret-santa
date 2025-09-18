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
        if ($group->owner_id === $user->id)
            return true;
        // participant: accepted invitation
        return $group->invitations()
            ->whereNotNull('accepted_at')
            ->where('invited_user_id', $user->id)
            ->exists();
    }

    /**
     * Determine if the user can update the group.
     */
    public function update(User $user, Group $group): bool
    {
        if ($group->has_draw) {
            return false; // immutable after draw
        }
        return $group->owner_id === $user->id;
    }

    /** Owner can always attempt to run draw (controller will guard idempotency). */
    public function runDraw(User $user, Group $group): bool
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

    /** Owner can remove a participant (not self). */
    public function removeParticipant(User $user, Group $group): bool
    {
        return $group->owner_id === $user->id;
    }
}

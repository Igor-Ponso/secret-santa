<?php

namespace App\Services;

use App\Models\Group;
use App\Models\User;

/**
 * Service encapsulating persistence concerns for Group aggregate.
 * Validation is performed at FormRequest level; only whitelisted fields are passed here.
 */
class GroupService
{
    /**
     * Create a group for a given owner.
     * @param array<string,mixed> $data
     */
    public function create(array $data, User $owner): Group
    {
        $payload = [
            'owner_id' => $owner->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'min_value' => $data['min_value'] ?? null,
            'max_value' => $data['max_value'] ?? null,
            'draw_at' => $data['draw_at'] ?? null,
        ];

        return Group::create($payload);
    }

    /**
     * Update group core attributes (ownership immutable here).
     * @param array<string,mixed> $data
     */
    public function update(Group $group, array $data): Group
    {
        $group->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'min_value' => $data['min_value'] ?? null,
            'max_value' => $data['max_value'] ?? null,
            'draw_at' => $data['draw_at'] ?? null,
        ]);

        return $group;
    }

    /**
     * Soft delete a group (cascading handled at DB constraints if configured).
     */
    public function delete(Group $group): void
    {
        $group->delete();
    }
}

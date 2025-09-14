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
            'min_gift_cents' => $data['min_gift_cents'] ?? null,
            'max_gift_cents' => $data['max_gift_cents'] ?? null,
            'draw_at' => $data['draw_at'] ?? null,
            'join_code' => $this->generateJoinCode(),
        ];

        return Group::create($payload);
    }

    /** Regenerate a unique join code for a group. */
    public function regenerateJoinCode(Group $group): Group
    {
        $group->join_code = $this->generateJoinCode();
        $group->save();
        return $group;
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
            'min_gift_cents' => $data['min_gift_cents'] ?? null,
            'max_gift_cents' => $data['max_gift_cents'] ?? null,
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
    private function generateJoinCode(): string
    {
        do {
            $code = strtoupper(substr(bin2hex(random_bytes(6)), 0, 12));
        } while (Group::where('join_code', $code)->exists());
        return $code;
    }
}

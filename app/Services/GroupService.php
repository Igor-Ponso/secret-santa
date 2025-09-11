<?php

namespace App\Services;

use App\Models\Group;
use App\Models\User;

class GroupService
{
    /**
     * Create a group for given owner.
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
}

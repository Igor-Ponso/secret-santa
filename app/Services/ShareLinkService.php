<?php

namespace App\Services;

use App\Models\Group;
use App\Models\GroupShareLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShareLinkService
{
    /**
     * Get or create a share link for a group. Always returns plain token.
     */
    public function getOrCreate(Group $group, User $user): array
    {
        return DB::transaction(function () use ($group, $user) {
            $record = GroupShareLink::where('group_id', $group->id)->first();
            if (!$record) {
                $plain = Str::random(48);
                $record = GroupShareLink::create([
                    'group_id' => $group->id,
                    'creator_id' => $user->id,
                    'token' => hash('sha256', $plain),
                    'last_rotated_at' => null,
                ]);
                return ['plain' => $plain, 'model' => $record];
            }
            // If existing, we cannot recover original plain (hash one-way), so rotate.
            $plain = Str::random(48);
            $record->forceFill([
                'token' => hash('sha256', $plain),
                'last_rotated_at' => now(),
            ])->save();
            return ['plain' => $plain, 'model' => $record];
        });
    }

    /** Resolve group by plain token. */
    public function findGroupByPlainToken(string $plain): ?Group
    {
        $hashed = hash('sha256', $plain);
        $link = GroupShareLink::where('token', $hashed)->first();
        return $link?->group;
    }

    /** Resolve share link model by plain token. */
    public function findLinkByPlainToken(string $plain): ?GroupShareLink
    {
        $hashed = hash('sha256', $plain);
        return GroupShareLink::where('token', $hashed)->first();
    }
}
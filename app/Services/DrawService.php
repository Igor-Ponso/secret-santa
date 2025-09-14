<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DrawService
{
    /**
     * Execute the Secret Santa draw for a group.
     * Rules:
     *  - Only accepted invitations (plus owner) participate.
     *  - No self-assignment.
     *  - Perfect cycle permutation.
     *  - Existing assignments are replaced atomically.
     */
    public function run(Group $group): void
    {
        DB::transaction(function () use ($group) {
            // Collect participants: owner + accepted invitations with invited_user_id
            $participants = collect();
            $owner = $group->owner()->first(['id']);
            if (!$owner)
                throw new RuntimeException('Group owner not found');
            $participants->push($owner->id);

            $accepted = GroupInvitation::where('group_id', $group->id)
                ->whereNotNull('accepted_at')
                ->whereNotNull('invited_user_id')
                ->pluck('invited_user_id');
            $participants = $participants->merge($accepted)->unique()->values();

            if ($participants->count() < 2) {
                throw new RuntimeException('Not enough participants for draw');
            }

            // Shuffle until no one matches themselves (derangement). Simple retry strategy.
            $giverIds = $participants->shuffle()->values();
            $receiverIds = $giverIds->shuffle()->values();
            $attempts = 0;
            $maxAttempts = 50;
            while ($attempts < $maxAttempts) {
                $valid = true;
                for ($i = 0; $i < $giverIds->count(); $i++) {
                    if ($giverIds[$i] === $receiverIds[$i]) {
                        $valid = false;
                        break;
                    }
                }
                if ($valid)
                    break;
                $receiverIds = $giverIds->shuffle()->values();
                $attempts++;
            }
            if (!$valid) {
                throw new RuntimeException('Unable to produce valid draw, try again');
            }

            // Clear old assignments
            Assignment::where('group_id', $group->id)->delete();

            // Persist assignments
            for ($i = 0; $i < $giverIds->count(); $i++) {
                Assignment::create([
                    'group_id' => $group->id,
                    'giver_user_id' => $giverIds[$i],
                    'receiver_user_id' => $receiverIds[$i],
                ]);
            }
        });
    }

    /**
     * Get the receiver user id for a given giver in a group.
     */
    public function receiverFor(Group $group, User $giver): ?int
    {
        return Assignment::where('group_id', $group->id)
            ->where('giver_user_id', $giver->id)
            ->value('receiver_user_id');
    }
}

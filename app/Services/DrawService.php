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
    public function run(Group $group): array
    {
        return DB::transaction(function () use ($group) {
            $participants = $group->participants()->get();
            if ($participants->count() < 2) {
                return ['success' => false, 'message' => 'Not enough participants'];
            }

            $ids = $participants->pluck('id')->all();
            $exclusions = $this->buildExclusionsMap($group);

            $solution = $this->solve($ids, $exclusions);
            if ($solution === null) {
                return ['success' => false, 'message' => 'Could not generate draw (constraints impossible?)'];
            }

            Assignment::where('group_id', $group->id)->delete();
            foreach ($solution as $giver => $receiver) {
                $assignment = new Assignment([
                    'group_id' => $group->id,
                    'giver_user_id' => $giver,
                    // Keep legacy plain column populated until a future migration makes it nullable/removed
                    'receiver_user_id' => $receiver,
                ]);
                // Store encrypted receiver alongside plain id
                $assignment->setEncryptedReceiver($receiver);
                $assignment->save();
            }

            $group->has_draw = true;
            $group->save();

            return ['success' => true];
        });
    }

    /**
     * Build exclusion map: giver_id => array<int,int> of forbidden receiver ids.
     * @return array<int, array<int,int>>
     */
    public function buildExclusionsMap(Group $group): array
    {
        return $group->exclusions()->get()->groupBy('user_id')->map(function ($rows) {
            return collect($rows)->pluck('excluded_user_id')->values()->all();
        })->toArray();
    }

    /**
     * Attempt to solve assignment respecting exclusions using backtracking with heuristics.
     * Returns mapping giver->receiver or null if impossible.
     *
     * Heuristics:
     *  - Order givers by most constrained first (fewest allowed receivers)
     *  - For each giver, iterate candidate receivers in random order (small shuffle) to add variability
     */
    public function solve(array $participants, array $exclusions): ?array
    {
        $n = count($participants);
        if ($n <= 1) {
            return null; // need at least 2 for a meaningful draw
        }

        // Precompute allowed receivers per giver (exclude self + explicit exclusions)
        $allowed = [];
        $set = array_flip($participants);
        foreach ($participants as $giver) {
            $forbidden = array_flip($exclusions[$giver] ?? []);
            $candidates = [];
            foreach ($participants as $recv) {
                if ($recv === $giver)
                    continue;
                if (isset($forbidden[$recv]))
                    continue;
                $candidates[] = $recv;
            }
            $allowed[$giver] = $candidates;
        }

        // Order givers by ascending number of allowed receivers (fail fast)
        $givers = $participants;
        usort($givers, function ($a, $b) use ($allowed) {
            return count($allowed[$a]) <=> count($allowed[$b]);
        });

        $assignment = [];
        $used = [];

        $limitNodes = 5000; // guardrail to avoid pathological blow-up
        $visited = 0;

        $backtrack = function ($idx) use (&$backtrack, &$assignment, &$used, $givers, $allowed, $n, &$visited, $limitNodes) {
            if ($visited++ > $limitNodes) {
                return false; // abort search; treat as failure
            }
            if ($idx === count($givers)) {
                return count($assignment) === $n; // complete
            }
            $giver = $givers[$idx];
            $candidates = $allowed[$giver];
            if (empty($candidates)) {
                return false;
            }
            // Small shuffle for variety
            if (count($candidates) > 1) {
                shuffle($candidates);
            }
            foreach ($candidates as $recv) {
                if (isset($used[$recv]))
                    continue;
                $assignment[$giver] = $recv;
                $used[$recv] = true;
                if ($backtrack($idx + 1)) {
                    return true;
                }
                unset($assignment[$giver]);
                unset($used[$recv]);
            }
            return false;
        };

        $ok = $backtrack(0);
        return $ok ? $assignment : null;
    }

    /**
     * Produce a sample solution (or null) without mutating database for preview.
     */
    public function sample(Group $group): ?array
    {
        $participants = $group->participants()->pluck('users.id')->push($group->owner_id)->unique()->values()->all();
        $exclusions = $this->buildExclusionsMap($group);
        return $this->solve($participants, $exclusions);
    }

    /**
     * Get the receiver user id for a given giver in a group.
     */
    public function receiverFor(Group $group, User $giver): ?int
    {
        $assignment = Assignment::where('group_id', $group->id)
            ->where('giver_user_id', $giver->id)
            ->first();
        if (!$assignment)
            return null;
        return $assignment->decrypted_receiver_id; // accessor handles cipher/plain
    }
}

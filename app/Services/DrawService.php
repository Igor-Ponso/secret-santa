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

            // Build exclusion map: giver_id => set of forbidden receiver_ids
            $exclusions = $group->exclusions()->get()->groupBy('user_id')->map(function ($rows) {
                return collect($rows)->pluck('excluded_user_id')->all();
            });

            $ids = $participants->pluck('id')->all();
            $maxAttempts = 80; // higher because constraints may reduce solution space
            $attempt = 0;
            $assignments = [];

            while ($attempt < $maxAttempts) {
                $receivers = $ids;
                shuffle($receivers);
                $valid = true;
                $assignments = [];
                foreach ($ids as $idx => $giver) {
                    $receiver = $receivers[$idx];
                    if ($giver === $receiver) { // self match
                        $valid = false;
                        break;
                    }
                    if (in_array($receiver, $exclusions->get($giver, []), true)) { // exclusion rule
                        $valid = false;
                        break;
                    }
                    $assignments[$giver] = $receiver;
                }
                if ($valid)
                    break;
                $attempt++;
            }

            if (!$assignments || count($assignments) !== count($ids)) {
                return ['success' => false, 'message' => 'Could not generate draw (constraints impossible?)'];
            }

            // Clear existing assignments before persisting
            Assignment::where('group_id', $group->id)->delete();
            foreach ($assignments as $giver => $receiver) {
                Assignment::create([
                    'group_id' => $group->id,
                    'giver_user_id' => $giver,
                    'receiver_user_id' => $receiver,
                ]);
            }

            $group->has_draw = true;
            $group->save();

            return ['success' => true];
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

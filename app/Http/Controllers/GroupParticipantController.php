<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Activity;

class GroupParticipantController extends Controller
{
    /**
     * Remove a participant (accepted invitation user) from the group.
     * Rules:
     * - Only owner
     * - Cannot remove owner
     * - Cannot remove if draw already executed (to preserve assignments)
     * - Must still leave at least 2 participants if draw not yet executed and draw potentially imminent
     */
    public function destroy(Group $group, User $user): RedirectResponse
    {
        $this->authorize('removeParticipant', $group);

        if ($user->id === $group->owner_id) {
            return back()->with('flash', ['error' => __('messages.participants.cannot_remove_owner')]);
        }

        // Check draw executed (assignments exist)
        $hasDraw = false;
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('assignments')) {
                $hasDraw = $group->assignments()->exists();
            }
        } catch (\Throwable $e) {
            $hasDraw = false;
        }
        if ($hasDraw) {
            return back()->with('flash', ['error' => __('messages.participants.cannot_remove_after_draw')]);
        }

        // Ensure user is actually a participant (accepted invitation)
        $invitation = $group->invitations()
            ->where('invited_user_id', $user->id)
            ->whereNotNull('accepted_at')
            ->first();
        if (!$invitation) {
            return back()->with('flash', ['error' => 'Usuário não participa do grupo.']);
        }

        // Count current participants (owner + accepted) BEFORE removal
        $acceptedCount = $group->invitations()
            ->whereNotNull('accepted_at')
            ->whereNotNull('invited_user_id')
            ->count();
        $participantTotal = 1 + $acceptedCount; // owner + accepted

        // After removal must not drop below 2 if there were >=2 (so that upcoming draw logic remains valid) - optional rule
        if ($participantTotal <= 2) {
            return back()->with('flash', ['error' => __('messages.participants.insufficient_after_removal')]);
        }

        DB::transaction(function () use ($invitation, $group, $user) {
            // Instead of hard deleting invitation, mark revoked to preserve audit trail
            $invitation->revoked_at = now();
            $invitation->save();

            Activity::create([
                'group_id' => $group->id,
                'user_id' => auth()->id(),
                'action' => 'participant.removed',
                'target_user_id' => $user->id,
                'meta' => null,
            ]);
        });

        Log::info('Participant removed from group', ['group_id' => $group->id, 'user_id' => $user->id]);

        return back()->with('flash', ['info' => __('messages.participants.removed')]);
    }
}

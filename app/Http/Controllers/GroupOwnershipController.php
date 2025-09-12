<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Activity;

class GroupOwnershipController extends Controller
{
    /**
     * Transfer group ownership to another accepted participant.
     */
    public function store(Request $request, Group $group)
    {
        $this->authorize('update', $group); // only current owner

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $targetId = (int) $validated['user_id'];

        if ($targetId === $group->owner_id) {
            return back()->with('flash', ['error' => 'Esse usuário já é o dono.']);
        }

        // Ensure target is an accepted participant
        $isParticipant = $group->invitations()
            ->whereNotNull('accepted_at')
            ->where('invited_user_id', $targetId)
            ->exists();

        if (!$isParticipant) {
            return back()->with('flash', ['error' => 'Usuário não é um participante aceito.']);
        }

        DB::transaction(function () use ($group, $targetId) {
            $group->owner_id = $targetId;
            $group->save();

            Activity::create([
                'group_id' => $group->id,
                'user_id' => auth()->id(),
                'action' => 'ownership.transferred',
                'target_user_id' => $targetId,
                'meta' => null,
            ]);
        });

        return back()->with('flash', ['success' => 'Ownership transferido com sucesso.']);
    }
}

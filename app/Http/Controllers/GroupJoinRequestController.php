<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupJoinRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class GroupJoinRequestController extends Controller
{
    /** User submits a group join code to create a pending join request. */
    public function joinByCode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20']
        ]);
        $user = $request->user();
        $group = \App\Models\Group::where('join_code', $data['code'])->first();
        if (!$group) {
            return back()->with('flash', ['error' => 'Código inválido.']);
        }
        if ($group->owner_id === $user->id) {
            return back()->with('flash', ['info' => 'Você já é o dono do grupo.']);
        }
        if ($group->isParticipant($user)) {
            return back()->with('flash', ['info' => 'Você já participa deste grupo.']);
        }
        $jr = GroupJoinRequest::firstOrCreate([
            'group_id' => $group->id,
            'user_id' => $user->id,
        ], ['status' => 'pending']);

        if ($jr->status === 'pending') {
            return redirect()->route('groups.index')->with('flash', ['success' => 'Pedido de entrada enviado para aprovação.']);
        }
        return redirect()->route('groups.index')->with('flash', ['info' => 'Já existe um resultado anterior para esse código.']);
    }
    public function store(Request $request, Group $group): RedirectResponse
    {
        $user = $request->user();
        if ($group->owner_id === $user->id) {
            return back()->with('flash', ['error' => 'Você já é dono do grupo.']);
        }
        // If user already participant (accepted invitation) block
        $alreadyParticipant = $group->participants()->where('users.id', $user->id)->exists();
        if ($alreadyParticipant) {
            return back()->with('flash', ['info' => 'Você já participa deste grupo.']);
        }
        $jr = GroupJoinRequest::firstOrCreate([
            'group_id' => $group->id,
            'user_id' => $user->id,
        ], ['status' => 'pending']);

        if ($jr->wasRecentlyCreated || $jr->status === 'pending') {
            return back()->with('flash', ['success' => 'Pedido de entrada enviado.']);
        }
        return back()->with('flash', ['info' => 'Já existe um histórico de pedido.']);
    }

    public function approve(Group $group, GroupJoinRequest $joinRequest): RedirectResponse
    {
        $this->authorize('update', $group);
        abort_unless($joinRequest->group_id === $group->id, 404);
        if ($joinRequest->status !== 'pending') {
            return back()->with('flash', ['error' => 'Não é possível aprovar.']);
        }
        $joinRequest->forceFill([
            'status' => 'approved',
            'approved_at' => now(),
        ])->save();
        // Create implicit accepted invitation for consistency
        \App\Models\GroupInvitation::create([
            'group_id' => $group->id,
            'inviter_id' => $group->owner_id,
            'invited_user_id' => $joinRequest->user_id,
            'email' => $joinRequest->user->email,
            'token' => hash('sha256', bin2hex(random_bytes(16))),
            'accepted_at' => now(),
            'expires_at' => now()->addDays(14),
        ]);
        return back()->with('flash', ['success' => 'Pedido aprovado.']);
    }

    public function deny(Group $group, GroupJoinRequest $joinRequest): RedirectResponse
    {
        $this->authorize('update', $group);
        abort_unless($joinRequest->group_id === $group->id, 404);
        if ($joinRequest->status !== 'pending') {
            return back()->with('flash', ['error' => 'Não é possível recusar.']);
        }
        $joinRequest->forceFill([
            'status' => 'denied',
            'denied_at' => now(),
        ])->save();
        return back()->with('flash', ['info' => 'Pedido recusado.']);
    }
}

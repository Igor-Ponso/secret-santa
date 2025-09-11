<?php

namespace App\Services;

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class InvitationService
{
    public function create(Group $group, User $inviter, string $email): GroupInvitation
    {
        return DB::transaction(function () use ($group, $inviter, $email) {
            $token = Str::random(48);
            $inv = GroupInvitation::create([
                'group_id' => $group->id,
                'inviter_id' => $inviter->id,
                'email' => $email,
                'token' => hash('sha256', $token), // store hashed
                'expires_at' => Carbon::now()->addDays(14),
            ]);
            return $inv->setAttribute('plain_token', $token); // attach plain for immediate use
        });
    }

    public function findByPlainToken(string $plain): ?GroupInvitation
    {
        $hashed = hash('sha256', $plain);
        return GroupInvitation::where('token', $hashed)->first();
    }

    public function accept(GroupInvitation $invitation, User $user): void
    {
        if ($invitation->accepted_at || $invitation->declined_at) return;
        $invitation->forceFill([
            'accepted_at' => Carbon::now(),
            'invited_user_id' => $user->id,
        ])->save();
    }

    public function decline(GroupInvitation $invitation): void
    {
        if ($invitation->accepted_at || $invitation->declined_at) return;
        $invitation->forceFill([
            'declined_at' => Carbon::now(),
        ])->save();
    }
}

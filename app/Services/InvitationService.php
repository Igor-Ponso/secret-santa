<?php

namespace App\Services;

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

/**
 * Service layer for creating and mutating group invitations.
 *
 * Responsibilities:
 *  - Token generation & hashing
 *  - Basic state transitions (accept / decline)
 *  - Lookup by plain token (constant-time-ish hashing path)
 */
class InvitationService
{
    /**
     * Create a new invitation.
     * Token stored hashed (sha256) to avoid leaking raw token if DB compromised.
     * The returned model carries a non-persisted attribute `plain_token` for one-time use (e.g. email composition).
     */
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

    /**
     * Find invitation by its plain token (hashed lookup).
     */
    public function findByPlainToken(string $plain): ?GroupInvitation
    {
        $hashed = hash('sha256', $plain);
        return GroupInvitation::where('token', $hashed)->first();
    }

    /**
     * Accept an invitation if still pending.
     */
    public function accept(GroupInvitation $invitation, User $user): void
    {
        if ($invitation->accepted_at || $invitation->declined_at)
            return;
        $invitation->forceFill([
            'accepted_at' => Carbon::now(),
            'invited_user_id' => $user->id,
        ])->save();
    }

    /**
     * Decline an invitation if still pending.
     */
    public function decline(GroupInvitation $invitation): void
    {
        if ($invitation->accepted_at || $invitation->declined_at)
            return;
        $invitation->forceFill([
            'declined_at' => Carbon::now(),
        ])->save();
    }

    /** Revoke an invitation (owner action). */
    public function revoke(GroupInvitation $invitation): void
    {
        if ($invitation->accepted_at || $invitation->revoked_at)
            return; // cannot revoke accepted or already revoked
        $invitation->forceFill([
            'revoked_at' => Carbon::now(),
        ])->save();
    }

    /**
     * Resend a pending (non accepted/declined/revoked/expired) invitation by regenerating token & extending expiry.
     * Returns updated model with transient plain_token attribute.
     */
    public function resend(GroupInvitation $invitation): ?GroupInvitation
    {
        if ($invitation->accepted_at || $invitation->declined_at || $invitation->revoked_at || $invitation->isExpired())
            return null;

        return DB::transaction(function () use ($invitation) {
            $token = Str::random(48);
            $invitation->forceFill([
                'token' => hash('sha256', $token),
                'expires_at' => Carbon::now()->addDays(14),
            ])->save();
            return $invitation->setAttribute('plain_token', $token);
        });
    }
}

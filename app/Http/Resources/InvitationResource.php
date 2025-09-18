<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\GroupInvitation;

/** @mixin GroupInvitation */
class InvitationResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var GroupInvitation $invitation */
        $invitation = $this->resource;
        $user = $request->user();

        $expired = $invitation->isExpired();
        $revoked = (bool) $invitation->revoked_at;
        $status = 'pending';
        if ($invitation->accepted_at) {
            $status = 'accepted';
        } elseif ($invitation->declined_at) {
            $status = 'declined';
        } elseif ($revoked) {
            $status = 'revoked';
        } elseif ($expired) {
            $status = 'expired';
        }

        $matchingEmail = $user && strcasecmp($user->email, $invitation->email) === 0;
        $canAccept = $status === 'pending' && $matchingEmail && !$expired && !$revoked;

        $viewerIsOwner = $user && $invitation->group->owner_id === $user->id;
        $viewerParticipates = $user ? $invitation->group->isParticipant($user) : false;

        $joinRequested = false;
        if ($user && !$viewerParticipates) {
            $joinRequested = \App\Models\GroupJoinRequest::where('group_id', $invitation->group_id)
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->exists();
        }

        $canRequestJoin = $user && !$viewerParticipates && $status !== 'invalid' && !$canAccept && !$joinRequested;

        return [
            'group' => $invitation->group->only(['id', 'name', 'description']),
            'inviter' => $invitation->inviter ? $invitation->inviter->only(['id', 'name']) : null,
            'email' => $matchingEmail ? $invitation->email : null,
            'status' => $status,
            'expired' => $expired,
            'revoked' => $revoked,
            'token' => $request->route('plainToken') ?? $request->route('token') ?? null,
            'viewer' => [
                'authenticated' => (bool) $user,
                'participates' => $viewerParticipates,
                'is_owner' => $viewerIsOwner,
                'email_mismatch' => $user ? !$matchingEmail : false,
                'can_accept' => $canAccept,
                'can_request_join' => $canRequestJoin,
                'join_requested' => $joinRequested,
            ],
        ];
    }
}

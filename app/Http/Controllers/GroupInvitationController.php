<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvitationRequest;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Notifications\GroupInvitationNotification;
use App\Services\InvitationService;
use App\Services\ShareLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

/**
 * Controller for internal (owner) invitation management.
 *
 * Security considerations:
 * - Rate limiting (implement via middleware) recommended to avoid abuse.
 * - Duplicate pending invitation check mitigates email enumeration.
 * - Only hashed tokens stored; plain token kept transiently for delivery.
 */
class GroupInvitationController extends Controller
{
    public function __construct(private InvitationService $service)
    {
    }

    /**
     * Return (or create) a sharable invitation link for the group.
     *
     * Selection rules:
     *  - Reuse latest pending (not accepted / declined / revoked / expired) invitation.
     *  - If none exists, create a new one bound to the current user (owner).
     *  - If a pending invitation exists but we lost the transient plain token (e.g. new request lifecycle),
     *    we regenerate by resending the invitation (new token) to avoid exposing hashed form.
     *
     * Rate limiting should be enforced at the route level via middleware.
     *
     * @param Group $group
     * @return JsonResponse
     */
    public function link(Group $group, ShareLinkService $shareLinkService): JsonResponse
    {
        $this->authorize('update', $group);
        $result = $shareLinkService->getOrCreate($group, auth()->user());
        return response()->json([
            'link' => route('invites.show', $result['plain'])
        ]);
    }

    /**
     * Create a nominal (email based) invitation for a group.
     *
     * Validation & business rules:
     *  - Block automatic reinvite if a declined invitation exists (forces join request flow).
     *  - Prevent duplicate pending invitation for the same email.
     *  - Sends notification with one-time plain token link.
     *
     * @param StoreInvitationRequest $request
     * @param Group $group
     * @return RedirectResponse
     */
    public function store(StoreInvitationRequest $request, Group $group): RedirectResponse
    {
        $this->authorize('update', $group);
        $data = $request->validated();
        $email = $data['email'];

        // Guard: prevent inviting the group owner (already a participant)
        if ($email === $group->owner->email) {
            return back()->with('flash', [
                'error' => __('messages.invitations.already_owner')
            ]);
        }

        // Guard: prevent inviting an email that already belongs to an accepted participant
        $acceptedParticipantEmailExists = $group->invitations()
            ->whereNotNull('accepted_at')
            ->where('email', $email)
            ->exists();
        if ($acceptedParticipantEmailExists) {
            return back()->with('flash', [
                'error' => __('messages.invitations.participant_exists')
            ]);
        }

        $declinedExists = $group->invitations()
            ->where('email', $email)
            ->whereNotNull('declined_at')
            ->exists();
        if ($declinedExists) {
            return back()->with('flash', [
                'error' => 'This email has already declined an invitation. User must request to join again.'
            ]);
        }

        $duplicate = $group->invitations()
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->whereNull('declined_at')
            ->whereNull('revoked_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
        if ($duplicate) {
            return back()->with('flash', ['error' => 'There is already a pending invitation for this email.']);
        }

        $invitation = $this->service->create($group, $request->user(), $email);
        if ($plain = $invitation->getAttribute('plain_token')) {
            $invitation->notify(new GroupInvitationNotification($group, $plain));
        }

        return redirect()->route('groups.show', [$group->id, 'tab' => 'invitations'])->with('flash', [
            'success' => 'Invitation created and email sent.'
        ]);
    }

    /**
     * Revoke a pending invitation.
     *
     * @param Group $group
     * @param GroupInvitation $invitation
     * @return RedirectResponse
     */
    public function revoke(Group $group, GroupInvitation $invitation): RedirectResponse
    {
        $this->authorize('update', $group);
        abort_unless($invitation->group_id === $group->id, 404);
        $this->service->revoke($invitation);
        return back()->with('flash', ['info' => __('messages.invitations.revoked')]);
    }

    /**
     * Resend a pending invitation regenerating token & extending expiration.
     *
     * @param Group $group
     * @param GroupInvitation $invitation
     * @return RedirectResponse
     */
    public function resend(Group $group, GroupInvitation $invitation): RedirectResponse
    {
        $this->authorize('update', $group);
        abort_unless($invitation->group_id === $group->id, 404);
        $updated = $this->service->resend($invitation);
        if (!$updated) {
            return back()->with('flash', ['error' => __('messages.invitations.cannot_resend')]);
        }
        if ($plain = $updated->getAttribute('plain_token')) {
            $updated->notify(new GroupInvitationNotification($group, $plain));
        }
        return back()->with('flash', ['success' => __('messages.invitations.resent')]);
    }
}
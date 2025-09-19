<?php

namespace App\Http\Controllers;

use App\Services\InvitationService;
use App\Http\Resources\InvitationResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;

/**
 * Public (token-based) invitation endpoints.
 * Requires authentication currently; could be relaxed to allow account creation flow.
 */
class PublicInvitationController extends Controller
{
    public function __construct(private InvitationService $service)
    {
    }

    /**
     * Display invitation landing page returning a normalized status instead of hard HTTP aborts.
     * This allows frontend to render context-specific i18n messages.
     */
    // Returns Inertia/JSON invite landing or redirects if viewer already participates
    public function show(Request $request, string $plainToken): Response|JsonResponse|RedirectResponse
    {
        $invitation = $this->service->findByPlainToken($plainToken);
        $user = $request->user();
        $component = $user ? 'Invites/Show' : 'Invites/Public';

        if (!$user) {
            // Persist token so after auth we can continue the flow.
            session(['pending_invite_token' => $plainToken]);
        }

        // Helper closure to standardize JSON/Inertia response emission
        $respond = function (array $payload) use ($request, $component) {
            if ($request->wantsJson()) {
                return response()->json([
                    'component' => $component,
                    'props' => $payload,
                    'url' => $request->getRequestUri(),
                    'version' => method_exists(\Inertia\Inertia::class, 'version') ? \Inertia\Inertia::getVersion() : null,
                ]);
            }
            return Inertia::render($component, $payload);
        };

        if (!$invitation) {
            // Try resolving as share link
            $shareGroup = app(\App\Services\ShareLinkService::class)->findGroupByPlainToken($plainToken);
            if ($shareGroup) {
                $shareGroup->loadMissing('owner:id,name');
                if ($user && $shareGroup->isParticipant($user)) {
                    return redirect()->route('groups.show', $shareGroup->id)
                        ->with('flash', ['info' => __('messages.participants.already_participating')]);
                }
                if (!$user) {
                    session(['pending_share_token' => $plainToken]);
                }
                $joinRequested = false;
                if ($user) {
                    $joinRequested = \App\Models\GroupJoinRequest::where('group_id', $shareGroup->id)
                        ->where('user_id', $user->id)
                        ->exists();
                }
                $canRequestJoin = $user
                    ? (!$shareGroup->isParticipant($user) && $shareGroup->owner_id !== $user->id && !$joinRequested)
                    : false;
                return $respond([
                    'invitation' => [
                        'group' => [
                            'id' => $shareGroup->id,
                            'name' => $shareGroup->name,
                            'owner' => [
                                'id' => $shareGroup->owner->id,
                                'name' => $shareGroup->owner->name,
                            ],
                        ],
                        'inviter' => null,
                        'email' => null,
                        'status' => 'share_link',
                        'expired' => false,
                        'revoked' => false,
                        'token' => $plainToken,
                        'viewer' => [
                            'authenticated' => (bool) $user,
                            'participates' => $user ? $shareGroup->isParticipant($user) : false,
                            'is_owner' => $user ? $shareGroup->owner_id === $user->id : false,
                            'email_mismatch' => false,
                            'can_accept' => false,
                            'can_request_join' => $canRequestJoin,
                            'join_requested' => $joinRequested,
                        ],
                    ],
                ]);
            }
            return $respond([
                'invitation' => [
                    'group' => null,
                    'inviter' => null,
                    'email' => null,
                    'status' => 'invalid',
                    'expired' => false,
                    'revoked' => false,
                    'token' => $plainToken,
                    'viewer' => [
                        'authenticated' => (bool) $user,
                        'participates' => false,
                        'is_owner' => false,
                        'email_mismatch' => false,
                        'can_accept' => false,
                        'can_request_join' => false,
                        'join_requested' => false,
                    ],
                ],
            ]);
        }

        $invitation->loadMissing(['inviter:id,name', 'group.owner:id', 'group.invitations:group_id,invited_user_id,accepted_at']);

        if ($user && $invitation->group->isParticipant($user)) {
            return redirect()->route('groups.show', $invitation->group_id)
                ->with('flash', ['info' => __('messages.participants.already_participating')]);
        }

        $resource = new InvitationResource($invitation);
        return $respond([
            'invitation' => $resource->toArray($request),
        ]);
    }

    /** Accept an invitation. */
    public function accept(Request $request, string $plainToken): RedirectResponse
    {
        $invitation = $this->service->findByPlainToken($plainToken);
        if (!$invitation) {
            abort(404);
        }
        abort_if($invitation->isExpired(), 410);

        // Only allow accept if authenticated user's email matches invitation email
        if (strtolower($request->user()->email) !== strtolower($invitation->email)) {
            abort(403, 'This invitation is not for your account.');
        }

        $this->service->accept($invitation, $request->user());
        // Redirect to onboarding if user has no wishlist items yet
        $hasItems = \App\Models\Wishlist::where('group_id', $invitation->group_id)
            ->where('user_id', $request->user()->id)
            ->exists();
        $targetRoute = $hasItems ? 'groups.wishlist.index' : 'groups.onboarding.show';
        return redirect()->route($targetRoute, $invitation->group_id)
            ->with('flash', ['success' => 'Invitation accepted']);
    }

    /** Decline an invitation. */
    public function decline(Request $request, string $plainToken): RedirectResponse
    {
        $invitation = $this->service->findByPlainToken($plainToken);
        if (!$invitation) {
            abort(404);
        }
        abort_if($invitation->isExpired(), 410);

        // Only allow decline if authenticated user's email matches invitation email
        if (strtolower($request->user()->email) !== strtolower($invitation->email)) {
            abort(403, 'This invitation is not for your account.');
        }

        $this->service->decline($invitation);
        return redirect()->route('groups.index')->with('flash', ['info' => 'Invitation declined']);
    }
}

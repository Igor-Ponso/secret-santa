<?php

namespace App\Http\Controllers;

use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
    public function show(string $plainToken): Response
    {
        $invitation = $this->service->findByPlainToken($plainToken);
        $user = request()->user();
        $component = $user ? 'Invites/Show' : 'Invites/Public';

        // If not authenticated, remember token to continue flow post login/registration
        if (!$user) {
            session(['pending_invite_token' => $plainToken]);
        }

        if (!$invitation) {
            return Inertia::render($component, [
                'invitation' => [
                    'group' => null,
                    'email' => null,
                    'status' => 'invalid',
                    'expired' => false,
                    'revoked' => false,
                    'token' => $plainToken,
                    'can_accept' => false,
                    'authenticated' => (bool) $user,
                ]
            ]);
        }

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

        $invitation->loadMissing(['inviter:id,name']);

        return Inertia::render($component, [
            'invitation' => [
                'group' => $invitation->group->only(['id', 'name', 'description']),
                'inviter' => $invitation->inviter ? $invitation->inviter->only(['id', 'name']) : null,
                // Only expose the invite email if authenticated *and* email matches; otherwise hide for privacy
                'email' => $matchingEmail ? $invitation->email : null,
                'status' => $status,
                'expired' => $expired,
                'revoked' => $revoked,
                'token' => $plainToken,
                'can_accept' => $canAccept,
                'authenticated' => (bool) $user,
                'email_mismatch' => $user ? (!$matchingEmail) : false,
            ]
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

        // Só permite aceitar se o e-mail do usuário autenticado for igual ao do convite
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

        // Só permite recusar se o e-mail do usuário autenticado for igual ao do convite
        if (strtolower($request->user()->email) !== strtolower($invitation->email)) {
            abort(403, 'This invitation is not for your account.');
        }

        $this->service->decline($invitation);
        return redirect()->route('groups.index')->with('flash', ['info' => 'Invitation declined']);
    }
}

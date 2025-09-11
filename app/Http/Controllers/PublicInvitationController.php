<?php

namespace App\Http\Controllers;

use App\Models\GroupInvitation;
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
    public function __construct(private InvitationService $service) {}

    /** Display invitation landing page. */
    public function show(string $plainToken): Response
    {
        $invitation = $this->service->findByPlainToken($plainToken);
        if (!$invitation) {
            abort(404);
        }
        abort_if($invitation->isExpired(), 410);

        return Inertia::render('Invites/Show', [
            'invitation' => [
                'group' => $invitation->group->only(['id','name','description']),
                'email' => $invitation->email,
                'status' => $invitation->accepted_at ? 'accepted' : ($invitation->declined_at ? 'declined' : 'pending'),
                'expired' => $invitation->isExpired(),
                'token' => $plainToken,
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

        $this->service->accept($invitation, $request->user());
        return redirect()->route('groups.index')->with('flash', ['success' => 'Invitation accepted']);
    }

    /** Decline an invitation. */
    public function decline(string $plainToken): RedirectResponse
    {
        $invitation = $this->service->findByPlainToken($plainToken);
        if (!$invitation) {
            abort(404);
        }
        abort_if($invitation->isExpired(), 410);

        $this->service->decline($invitation);
        return redirect()->route('groups.index')->with('flash', ['info' => 'Invitation declined']);
    }
}

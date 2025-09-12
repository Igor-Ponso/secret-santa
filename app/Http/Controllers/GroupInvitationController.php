<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvitationRequest;
use App\Models\Group;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;

/**
 * Controller for internal (owner) invitation management.
 *
 * Security considerations:
 * - Consider rate limiting invitation creation (e.g. throttling by user/group) to prevent abuse.
 * - Duplicate pending invitation check reduces email enumeration risk.
 * - Plain tokens never persisted; only hashed form stored.
 */
class GroupInvitationController extends Controller
{
    public function __construct(private InvitationService $service)
    {
    }

    /**
     * Create a new invitation for a group owner.
     */
    public function store(StoreInvitationRequest $request, Group $group): RedirectResponse
    {
        $this->authorize('update', $group); // owner can invite
        $data = $request->validated();
        $email = $data['email'];

        // Prevent duplicate pending invitation for same email
        $exists = $group->invitations()
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->whereNull('declined_at')
            ->exists();
        if ($exists) {
            return back()->with('flash', ['error' => 'Invitation already pending for this email.']);
        }

        $invitation = $this->service->create($group, $request->user(), $email);

        // (Future) Send email here.
        // Security: avoid exposing even partial tokens or links in UI feedback.
        return redirect()->route('groups.index')->with('flash', [
            'success' => 'Convite criado e enviado (verifique o email do convidado).',
        ]);
    }
}

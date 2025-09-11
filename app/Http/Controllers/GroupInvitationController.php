<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvitationRequest;
use App\Models\Group;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;

class GroupInvitationController extends Controller
{
    public function __construct(private InvitationService $service) {}

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

        // The plain token is not persisted; retrieve it from model attribute
        $plain = $invitation->getAttribute('plain_token');
        $url = route('invites.show', $plain);

        // (Future) Send email here.

    return redirect()->route('groups.index')->with('flash', ['success' => 'Invitation created', 'info' => $url]);
    }
}

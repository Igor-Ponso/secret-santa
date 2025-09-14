<?php

namespace App\Http\Controllers;

use App\Models\GroupInvitation;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserInvitationActionController extends Controller
{
    public function __construct(private InvitationService $service)
    {
    }

    protected function ensureOwnership(GroupInvitation $invitation, Request $request): void
    {
        if ($invitation->email !== $request->user()->email) {
            abort(Response::HTTP_FORBIDDEN);
        }
        if ($invitation->isExpired()) {
            abort(Response::HTTP_GONE);
        }
    }

    public function accept(Request $request, GroupInvitation $invitation): RedirectResponse
    {
        $this->ensureOwnership($invitation, $request);
        $this->service->accept($invitation, $request->user());
        return back()->with('flash', ['success' => 'Convite aceito.']);
    }

    public function decline(Request $request, GroupInvitation $invitation): RedirectResponse
    {
        $this->ensureOwnership($invitation, $request);
        $this->service->decline($invitation);
        return back()->with('flash', ['success' => 'Convite recusado.']);
    }
}

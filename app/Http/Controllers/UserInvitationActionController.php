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
        $hasItems = \App\Models\Wishlist::where('group_id', $invitation->group_id)
            ->where('user_id', $request->user()->id)
            ->exists();
        $route = $hasItems ? 'groups.wishlist.index' : 'groups.onboarding.show';
        return redirect()->route($route, $invitation->group_id)->with('flash', ['success' => 'Convite aceito.']);
    }

    public function decline(Request $request, GroupInvitation $invitation): RedirectResponse
    {
        $this->ensureOwnership($invitation, $request);
        $this->service->decline($invitation);
        return back()->with('flash', ['success' => 'Convite recusado.']);
    }
}

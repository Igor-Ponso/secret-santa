<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\DrawService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use RuntimeException;

class DrawController extends Controller
{
    public function run(Request $request, Group $group, DrawService $service)
    {
        Gate::authorize('update', $group); // owner

        if ($group->assignments()->exists()) {
            return back()->with('error', 'O sorteio já foi realizado.');
        }

        try {
            $service->run($group);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Sorteio realizado com sucesso!');
    }

    public function recipient(Request $request, Group $group, DrawService $service)
    {
        // Must be participant (owner or accepted invitee)
        $user = $request->user();
        if (!$group->isParticipant($user)) {
            abort(403);
        }
        $receiverId = $service->receiverFor($group, $user);
        if (!$receiverId) {
            return response()->json(['data' => null]);
        }

        $receiver = $group->participants()->where('users.id', $receiverId)->first();
        if (!$receiver) {
            return response()->json(['data' => null]);
        }

        // Fetch wishlist items for this receiver in this group (limit 50 for safety)
        $wishlist = \App\Models\Wishlist::where('group_id', $group->id)
            ->where('user_id', $receiver->id)
            ->orderBy('id')
            ->limit(50)
            ->get(['id', 'item', 'note', 'url']);

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $receiver->id,
                    'name' => $receiver->name,
                ],
                'wishlist' => $wishlist,
            ]
        ]);
    }
}

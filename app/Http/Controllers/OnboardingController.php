<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class OnboardingController extends Controller
{
    /** Display onboarding page if user just accepted invitation and has no wishlist items. */
    public function show(Group $group): Response|RedirectResponse
    {
        $user = Auth::user();
        $hasItems = Wishlist::where('group_id', $group->id)->where('user_id', $user->id)->exists();
        if ($hasItems) {
            return redirect()->route('groups.wishlist.index', $group);
        }
        return Inertia::render('Groups/Onboarding', [
            'group' => $group->only(['id', 'name']),
        ]);
    }

    /** Batch create up to 3 wishlist items atomically. */
    public function store(Request $request, Group $group): RedirectResponse
    {
        $data = $request->validate([
            'items' => 'required|array|min:1|max:3',
            'items.*.item' => 'required|string|max:255',
            'items.*.note' => 'nullable|string|max:255',
            'items.*.url' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($data, $group, $request) {
            foreach ($data['items'] as $row) {
                $url = $row['url'] ?? null;
                if ($url && !preg_match('~^https?://~i', $url)) {
                    $url = 'https://' . ltrim($url);
                }
                Wishlist::create([
                    'user_id' => $request->user()->id,
                    'group_id' => $group->id,
                    'item' => $row['item'],
                    'note' => $row['note'] ?? null,
                    'url' => $url,
                ]);
            }
        });

        return redirect()->route('groups.wishlist.index', $group)->with('flash', ['success' => __('messages.wishlist.initialized')]);
    }

    /** Skip adding items. */
    public function skip(Group $group): RedirectResponse
    {
        return redirect()->route('groups.wishlist.index', $group)->with('flash', ['info' => __('messages.wishlist.can_add_later')]);
    }
}

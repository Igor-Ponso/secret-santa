<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for managing wishlists (per user, per group).
 */
class WishlistController extends Controller
{
    /**
     * Show the wishlist for the current user in a group.
     */
    public function index(Group $group): Response
    {
        $user = Auth::user();
        $order = request('order', 'created');
        $query = Wishlist::where('group_id', $group->id)
            ->where('user_id', $user->id);
        if ($order === 'alpha') {
            $query->orderBy('item');
        } else {
            $query->orderBy('created_at');
        }
        $items = $query->get(['id', 'item', 'note', 'url']);
        return Inertia::render('Wishlists/Index', [
            'group' => $group->only(['id', 'name']),
            'items' => $items,
            'order' => $order,
        ]);
    }

    /**
     * Store a new wishlist item.
     */
    public function store(Request $request, Group $group): RedirectResponse
    {
        // Normalize URL (prepend https:// if missing scheme)
        if ($request->filled('url') && !preg_match('~^https?://~i', $request->input('url'))) {
            $request->merge(['url' => 'https://' . ltrim($request->input('url'))]);
        }
        $request->validate([
            'item' => 'required|string|max:255',
            'note' => 'nullable|string|max:255',
            'url' => 'nullable|url|max:255',
        ]);
        Wishlist::create([
            'user_id' => $request->user()->id,
            'group_id' => $group->id,
            'item' => $request->item,
            'note' => $request->note,
            'url' => $request->url,
        ]);
        return back()->with('flash', ['success' => 'Wishlist item added']);
    }

    /**
     * Update a wishlist item.
     */
    public function update(Request $request, Group $group, Wishlist $wishlist): RedirectResponse
    {
        $this->authorize('update', $wishlist);
        if ($request->filled('url') && !preg_match('~^https?://~i', $request->input('url'))) {
            $request->merge(['url' => 'https://' . ltrim($request->input('url'))]);
        }
        $request->validate([
            'item' => 'required|string|max:255',
            'note' => 'nullable|string|max:255',
            'url' => 'nullable|url|max:255',
        ]);
        $wishlist->update($request->only(['item', 'note', 'url']));
        return back()->with('flash', ['info' => 'Wishlist item updated']);
    }

    /**
     * Delete a wishlist item.
     */
    public function destroy(Group $group, Wishlist $wishlist): RedirectResponse
    {
        $this->authorize('delete', $wishlist);
        $wishlist->delete();
        return back()->with('flash', ['info' => 'Wishlist item removed']);
    }
}

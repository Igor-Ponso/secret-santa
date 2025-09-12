<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use App\Models\Group;
use App\Services\GroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller handling CRUD operations for Groups.
 * All routes protected by auth middleware. Authorization for ownership enforced via policy on mutating endpoints.
 */
class GroupController extends Controller
{
    public function __construct(private GroupService $service)
    {
    }

    /**
     * List groups owned by the authenticated user.
     */
    public function index(): Response
    {
        $userId = Auth::id();

        $wishlistCountSub = \App\Models\Wishlist::query()
            ->selectRaw('COUNT(*)')
            ->whereColumn('wishlists.group_id', 'groups.id')
            ->where('wishlists.user_id', $userId);

        $ownedGroups = Group::query()
            ->where('owner_id', $userId)
            ->with([
                'invitations' => function ($q) {
                    $q->latest('id')->select(['id', 'group_id', 'email', 'accepted_at', 'declined_at']);
                }
            ])
            ->select([
                'id',
                'name',
                'description',
                'min_value',
                'max_value',
                'draw_at',
                'created_at'
            ])
            ->selectSub($wishlistCountSub, 'wishlist_count')
            ->latest('id')
            ->get()
            ->map(function (Group $g) {
                return [
                    'id' => $g->id,
                    'name' => $g->name,
                    'description' => $g->description,
                    'min_value' => $g->min_value,
                    'max_value' => $g->max_value,
                    'draw_at' => $g->draw_at,
                    'created_at' => $g->created_at,
                    'wishlist_count' => (int) $g->wishlist_count,
                    'invitations' => $g->invitations->map(fn($i) => [
                        'email' => $i->email,
                        'status' => $i->accepted_at ? 'accepted' : ($i->declined_at ? 'declined' : 'pending'),
                    ])
                ];
            });

        // Participating groups (accepted invitations) excluding ones the user owns
        $participatingGroups = Group::query()
            ->where('owner_id', '!=', $userId)
            ->whereHas('invitations', function ($q) use ($userId) {
                $q->whereNotNull('accepted_at')->where('invited_user_id', $userId);
            })
            ->select([
                'id',
                'name',
                'description',
                'min_value',
                'max_value',
                'draw_at',
                'created_at'
            ])
            ->latest('id')
            ->get()
            ->map(function (Group $g) use ($userId) {
                // wishlist_count for the current user within that group
                $wishlistCount = \App\Models\Wishlist::where('group_id', $g->id)->where('user_id', $userId)->count();
                return [
                    'id' => $g->id,
                    'name' => $g->name,
                    'description' => $g->description,
                    'min_value' => $g->min_value,
                    'max_value' => $g->max_value,
                    'draw_at' => $g->draw_at,
                    'created_at' => $g->created_at,
                    'wishlist_count' => (int) $wishlistCount,
                ];
            });

        return Inertia::render('Groups/Index', [
            'groups' => $ownedGroups,
            'participating' => $participatingGroups,
        ]);
    }

    /** Show create form. */
    public function create(): Response
    {
        return Inertia::render('Groups/Create');
    }

    /** Persist a new group. */
    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $group = $this->service->create($request->validated(), $request->user());

        return redirect()->route('groups.index')
            ->with('flash', ['success' => 'Group created successfully']);
    }

    /** Edit form (authorization via policy). */
    public function edit(Group $group): Response
    {
        $this->authorize('update', $group);
        return Inertia::render('Groups/Edit', [
            'group' => $group->only(['id', 'name', 'description', 'min_value', 'max_value', 'draw_at'])
        ]);
    }

    /** Update existing group. */
    public function update(UpdateGroupRequest $request, Group $group): RedirectResponse
    {
        $this->authorize('update', $group);
        $this->service->update($group, $request->validated());

        return redirect()->route('groups.index')
            ->with('flash', ['info' => 'Group updated successfully']);
    }

    /** Soft delete a group.
     *  
     * @param Group $group
     * 
     * @return RedirectResponse
     */
    public function destroy(Group $group): RedirectResponse
    {
        $this->authorize('delete', $group);
        $this->service->delete($group);

        return redirect()->route('groups.index')
            ->with('flash', ['success' => 'Group deleted successfully']);
    }

    /** Show group details & draw status */
    public function show(Group $group): Response
    {
        $this->authorize('view', $group);
        $hasDraw = false;
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('assignments')) {
                $hasDraw = $group->assignments()->exists();
            }
        } catch (\Throwable $e) {
            $hasDraw = false; // silently fallback if table truly missing
        }

        // Participant count = owner + accepted invitations with attached user accounts
        $acceptedCount = $group->invitations()
            ->whereNotNull('accepted_at')
            ->whereNotNull('invited_user_id')
            ->count();
        $participantCount = 1 + $acceptedCount; // owner always counts

        $canDraw = !$hasDraw && $participantCount >= 2; // minimum 2 participants required

        // Participants (owner + accepted) - expose only id & name for privacy
        $participants = $group->participants()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($u) => ['id' => $u->id, 'name' => $u->name]);

        $isOwner = auth()->id() === $group->owner_id;

        // For owner, provide invitations summary (including pending) to manage engagement
        $invitationsSummary = [];
        if ($isOwner) {
            $invitationsSummary = $group->invitations()
                ->latest('id')
                ->get(['id', 'email', 'accepted_at', 'declined_at'])
                ->map(fn($inv) => [
                    'id' => $inv->id,
                    'email' => $inv->email,
                    'status' => $inv->accepted_at ? 'accepted' : ($inv->declined_at ? 'declined' : 'pending'),
                ]);
        }

        return Inertia::render('Groups/Show', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'is_owner' => $isOwner,
                'has_draw' => $hasDraw,
                'participant_count' => $participantCount,
                'can_draw' => $canDraw,
                'participants' => $participants,
                'invitations' => $invitationsSummary,
            ]
        ]);
    }
}

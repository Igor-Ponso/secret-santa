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
use Illuminate\Http\Request;

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
                    $q->latest('id')->select(['id', 'group_id', 'email', 'accepted_at', 'declined_at', 'revoked_at', 'expires_at']);
                }
            ])
            ->select([
                'id',
                'name',
                'description',
                'min_gift_cents',
                'max_gift_cents',
                'currency',
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
                    'min_gift_cents' => $g->min_gift_cents,
                    'max_gift_cents' => $g->max_gift_cents,
                    'currency' => $g->currency,
                    'draw_at' => $g->draw_at,
                    'created_at' => $g->created_at,
                    'wishlist_count' => (int) $g->wishlist_count,
                    'invitations' => $g->invitations->map(fn($i) => [
                        'email' => $i->email,
                        'status' => method_exists($i, 'status') ? $i->status() : ($i->accepted_at ? 'accepted' : ($i->declined_at ? 'declined' : 'pending')),
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
                'min_gift_cents',
                'max_gift_cents',
                'currency',
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
                    'min_gift_cents' => $g->min_gift_cents,
                    'max_gift_cents' => $g->max_gift_cents,
                    'currency' => $g->currency,
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
            'group' => $group->only(['id', 'name', 'description', 'min_gift_cents', 'max_gift_cents', 'draw_at'])
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
    public function show(Request $request, Group $group): Response
    {
        // Membership & existence already enforced by EnsureGroupMembership middleware -> 404 if not a participant.
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

        // Draw date semantics: draw_at is stored as date (Y-m-d). Owner is allowed to manually trigger
        // even before the date (business rule) but we still expose how many days remain for UI messaging.
        $today = now()->startOfDay();
        $drawDate = $group->draw_at ? \Carbon\Carbon::parse($group->draw_at) : null; // ensure Carbon instance
        $daysUntilDraw = null;
        if ($drawDate) {
            $daysUntilDraw = $today->diffInDays($drawDate, false); // negative if past
        }
        // Manual draw policy (business rule per conversation): owner may draw early as long as not already drawn and min participants reached
        $canManualDraw = $canDraw; // base rule uses participant threshold & not drawn yet
        $canManualDrawTodayOnly = false; // set true if we ever restrict to same-day in future

        // Participants (owner + accepted) - expose only id & name for privacy
        // Build participants (owner + accepted invitation users) with accepted_at when available.
        $acceptedParticipants = $group->invitations()
            ->whereNotNull('accepted_at')
            ->whereNotNull('invited_user_id')
            ->with(['invitedUser:id,name'])
            ->get()
            ->map(fn($inv) => [
                'id' => $inv->invited_user_id,
                'name' => $inv->invitedUser?->name ?? 'Usuário',
                'accepted_at' => $inv->accepted_at?->toISOString(),
            ]);

        $participants = collect([
            [
                'id' => $group->owner_id,
                'name' => $group->owner?->name ?? 'Dono',
                'accepted_at' => null,
                'wishlist_count' => $group->wishlists()->where('user_id', $group->owner_id)->count(),
            ],
        ])->merge(
                $acceptedParticipants->map(function ($p) use ($group) {
                    $p['wishlist_count'] = $group->wishlists()->where('user_id', $p['id'])->count();
                    return $p;
                })
            )->sortBy('name')->values();

        $isOwner = auth()->id() === $group->owner_id;

        // For owner, provide invitations summary (including pending) to manage engagement
        $invitationsSummary = [];
        $joinRequestsSummary = [];
        $invitationsMeta = [];
        $joinRequestsMeta = [];
        if ($isOwner) {
            $inviteSearch = trim((string) $request->query('invite_search', ''));
            $jrSearch = trim((string) $request->query('jr_search', ''));
            $invitePage = (int) $request->query('invite_page', 1);
            $jrPage = (int) $request->query('jr_page', 1);
            $perPage = 10;

            $inviteQuery = $group->invitations()->latest('id');
            if ($inviteSearch !== '') {
                $inviteQuery->where('email', 'like', "%{$inviteSearch}%");
            }
            $invitePaginator = $inviteQuery->select(['id', 'email', 'accepted_at', 'declined_at', 'revoked_at', 'expires_at', 'created_at'])
                ->paginate($perPage, ['*'], 'invite_page', $invitePage);
            $invitationsSummary = collect($invitePaginator->items())
                ->map(fn($inv) => [
                    'id' => $inv->id,
                    'email' => $inv->email,
                    'status' => $inv->status(),
                    'created_at' => $inv->created_at?->toISOString(),
                    'accepted_at' => $inv->accepted_at?->toISOString(),
                    'declined_at' => $inv->declined_at?->toISOString(),
                    'revoked_at' => $inv->revoked_at?->toISOString(),
                    'expires_at' => $inv->expires_at?->toISOString(),
                ]);
            $invitationsMeta = [
                'current_page' => $invitePaginator->currentPage(),
                'last_page' => $invitePaginator->lastPage(),
                'per_page' => $invitePaginator->perPage(),
                'total' => $invitePaginator->total(),
                'search' => $inviteSearch,
            ];

            $jrQuery = $group->joinRequests()->latest('id')->with('user');
            if ($jrSearch !== '') {
                $jrQuery->whereHas('user', function ($q) use ($jrSearch) {
                    $q->where('name', 'like', "%{$jrSearch}%")->orWhere('email', 'like', "%{$jrSearch}%");
                });
            }
            $jrPaginator = $jrQuery->select(['id', 'user_id', 'status', 'approved_at', 'denied_at', 'created_at'])
                ->paginate($perPage, ['*'], 'jr_page', $jrPage);
            $joinRequestsSummary = collect($jrPaginator->items())
                ->map(fn($jr) => [
                    'id' => $jr->id,
                    'user' => $jr->user?->only(['id', 'name', 'email']),
                    'status' => $jr->status,
                    'created_at' => $jr->created_at?->toISOString(),
                    'approved_at' => $jr->approved_at?->toISOString(),
                    'denied_at' => $jr->denied_at?->toISOString(),
                ]);
            $joinRequestsMeta = [
                'current_page' => $jrPaginator->currentPage(),
                'last_page' => $jrPaginator->lastPage(),
                'per_page' => $jrPaginator->perPage(),
                'total' => $jrPaginator->total(),
                'search' => $jrSearch,
            ];
        }

        // Readiness metrics for owner UI
        $metrics = [];
        if ($isOwner) {
            $all = $group->invitations()->get(['id', 'accepted_at', 'declined_at', 'revoked_at']);
            $pending = $all->filter(fn($i) => !$i->accepted_at && !$i->declined_at && !$i->revoked_at)->count();
            $accepted = $all->filter(fn($i) => $i->accepted_at)->count();
            $declined = $all->filter(fn($i) => $i->declined_at)->count();
            $revoked = $all->filter(fn($i) => $i->revoked_at)->count();
            // Minimum participants rule (>=2) already used for draw enablement
            $minParticipantsMet = $participantCount >= 2;
            // Wishlist coverage: participants (excluding owner?) -> consider all participants
            $participantIds = $participants->pluck('id')->all();
            $withWishlist = \App\Models\Wishlist::whereIn('user_id', $participantIds)->where('group_id', $group->id)->distinct('user_id')->count('user_id');
            $coveragePercent = $participantCount > 0 ? round(($withWishlist / $participantCount) * 100) : 0;
            $threshold = (int) config('groups.readiness_wishlist_threshold', 50);
            $readyForDraw = $minParticipantsMet && $coveragePercent >= $threshold; // configurable heuristic
            $metrics = [
                'pending' => $pending,
                'accepted' => $accepted,
                'declined' => $declined,
                'revoked' => $revoked,
                'min_participants_met' => $minParticipantsMet,
                'wishlist_coverage_percent' => $coveragePercent,
                'ready_for_draw' => $readyForDraw,
                'readiness_threshold' => $threshold,
            ];
        }

        // Sanitize requested tab param
        $requestedTab = (string) $request->query('tab', 'participants');
        $validTabs = ['participants', 'invitations', 'join_requests'];
        $initialTab = in_array($requestedTab, $validTabs, true) ? $requestedTab : 'participants';
        if ($requestedTab && $requestedTab !== $initialTab) {
            if (app()->environment('local', 'development')) {
                \Log::warning('Invalid tab param on group.show', [
                    'requested' => $requestedTab,
                    'group_id' => $group->id,
                    'user_id' => auth()->id(),
                ]);
            }
            // Provide a flash warning only once in this request cycle
            session()->flash('flash', [
                'warning' => __('messages.participants.invalid_tab')
            ]);
        }

        return Inertia::render('Groups/Show', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                // Expose owner_id so frontend can correctly identify owner row (hide remove button etc.)
                'owner_id' => $group->owner_id,
                'is_owner' => $isOwner,
                'has_draw' => $hasDraw,
                'participant_count' => $participantCount,
                'can_draw' => $canDraw,
                'draw_date' => $drawDate?->toDateString(),
                'days_until_draw' => $daysUntilDraw,
                // Frontend flags for manual draw button enablement / messaging
                'can_manual_draw' => $canManualDraw,
                'can_manual_draw_today_only' => $canManualDrawTodayOnly,
                'participants' => $participants,
                'invitations' => $invitationsSummary,
                'invitations_meta' => $invitationsMeta,
                'join_code' => $isOwner ? $group->join_code : null,
                'join_requests' => $joinRequestsSummary,
                'join_requests_meta' => $joinRequestsMeta,
                'pending_join_requests_count' => $isOwner ? $group->joinRequests()->where('status', 'pending')->count() : 0,
                'metrics' => $metrics,
                'min_gift_cents' => $group->min_gift_cents,
                'max_gift_cents' => $group->max_gift_cents,
                'currency' => $group->currency,
                'exclusions' => $isOwner ? $group->exclusions()->with(['user:id,name', 'excludedUser:id,name'])->get()->map(fn($e) => [
                    'id' => $e->id,
                    'user' => ['id' => $e->user_id, 'name' => $e->user?->name],
                    'excluded_user' => ['id' => $e->excluded_user_id, 'name' => $e->excludedUser?->name],
                ]) : [],
                'activities' => \App\Models\Activity::where('group_id', $group->id)
                    ->latest('id')
                    ->limit(10)
                    ->get(['id', 'action', 'user_id', 'target_user_id', 'created_at'])
                    ->map(fn($a) => [
                        'id' => $a->id,
                        'action' => $a->action,
                        'user_id' => $a->user_id,
                        'target_user_id' => $a->target_user_id,
                        'created_at' => $a->created_at?->toISOString(),
                    ]),
                'initial_tab' => $initialTab,
            ]
        ]);
    }

    /** Regenerate join code (owner only). */
    public function regenerateCode(Request $request, Group $group, \App\Services\GroupService $service): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $group);
        $service->regenerateJoinCode($group);
        return back()->with('flash', ['success' => 'Novo código gerado.']);
    }
}

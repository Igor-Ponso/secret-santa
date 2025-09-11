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
        $groups = Group::query()
            ->where('owner_id', Auth::id())
            ->with([
                'invitations' => function ($q) {
                    $q->latest('id')->select(['id', 'group_id', 'email', 'accepted_at', 'declined_at']);
                }
            ])
            ->latest('id')
            ->select(['id', 'name', 'description', 'min_value', 'max_value', 'draw_at', 'created_at'])
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
                    'invitations' => $g->invitations->map(fn($i) => [
                        'email' => $i->email,
                        'status' => $i->accepted_at ? 'accepted' : ($i->declined_at ? 'declined' : 'pending'),
                    ])
                ];
            });

        return Inertia::render('Groups/Index', [
            'groups' => $groups,
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
}

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

class GroupController extends Controller
{
    public function __construct(private GroupService $service)
    {
    }

    public function index(): Response
    {
        $groups = Group::query()
            ->where('owner_id', Auth::id())
            ->latest('id')
            ->select(['id', 'name', 'description', 'min_value', 'max_value', 'draw_at', 'created_at'])
            ->get();

        return Inertia::render('Groups/Index', [
            'groups' => $groups,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Groups/Create');
    }

    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $group = $this->service->create($request->validated(), $request->user());

        return redirect()->route('groups.index')
            ->with('flash', ['success' => 'Group created successfully']);
    }

    public function edit(Group $group): Response
    {
        $this->authorize('update', $group);
        return Inertia::render('Groups/Edit', [
            'group' => $group->only(['id', 'name', 'description', 'min_value', 'max_value', 'draw_at'])
        ]);
    }

    public function update(UpdateGroupRequest $request, Group $group): RedirectResponse
    {
        $this->authorize('update', $group);
        $this->service->update($group, $request->validated());

        return redirect()->route('groups.index')
            ->with('flash', ['success' => 'Group updated successfully']);
    }
}

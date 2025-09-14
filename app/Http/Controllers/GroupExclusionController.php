<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGroupExclusionRequest;
use App\Models\Group;
use App\Models\GroupExclusion;
use Illuminate\Http\RedirectResponse;

class GroupExclusionController extends Controller
{
    public function store(StoreGroupExclusionRequest $request, Group $group): RedirectResponse
    {
        $data = $request->validated();
        GroupExclusion::firstOrCreate([
            'group_id' => $group->id,
            'user_id' => $data['user_id'],
            'excluded_user_id' => $data['excluded_user_id'],
        ]);
        return back()->with('status', 'exclusion-added');
    }

    public function destroy(Group $group, GroupExclusion $exclusion): RedirectResponse
    {
        if ($exclusion->group_id !== $group->id) {
            abort(404);
        }
        if ($group->owner_id !== auth()->id()) {
            abort(403);
        }
        $exclusion->delete();
        return back()->with('status', 'exclusion-removed');
    }
}

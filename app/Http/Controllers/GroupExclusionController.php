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
        // Build current exclusion map to check impossibility after additions
        $participants = $group->participants()->pluck('users.id')->push($group->owner_id)->unique();

        $toInsert = [
            ['group_id' => $group->id, 'user_id' => $data['user_id'], 'excluded_user_id' => $data['excluded_user_id']],
        ];
        if (!empty($data['reciprocal'])) {
            $toInsert[] = ['group_id' => $group->id, 'user_id' => $data['excluded_user_id'], 'excluded_user_id' => $data['user_id']];
        }

        $createdIds = [];
        foreach ($toInsert as $row) {
            $model = GroupExclusion::firstOrCreate($row);
            // Only treat as newly created if it was recently persisted (wasRecentlyCreated flag)
            if ($model->wasRecentlyCreated) {
                $createdIds[] = $model->id;
            }
        }

        // Re-check impossibility: any participant excluding all others?
        $exclusionGroups = $group->exclusions()->get()->groupBy('user_id');
        $total = $participants->count();
        $impossible = false;
        foreach ($participants as $pid) {
            $count = isset($exclusionGroups[$pid]) ? $exclusionGroups[$pid]->pluck('excluded_user_id')->unique()->count() : 0;
            if ($total > 1 && $count >= $total - 1) {
                $impossible = true;
                break;
            }
        }
        if ($impossible) {
            // Rollback only rows that were newly created in this request
            if (!empty($createdIds)) {
                GroupExclusion::whereIn('id', $createdIds)->delete();
            }
            return back()->with('flash.error', __('groups.exclusions_error_impossible'));
        }
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

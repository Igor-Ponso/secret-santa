<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGroupExclusionRequest;
use App\Models\Group;
use App\Models\GroupExclusion;
use App\Services\ExclusionService;
use App\Services\DrawService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class GroupExclusionController extends Controller
{
    public function store(StoreGroupExclusionRequest $request, Group $group, ExclusionService $service): RedirectResponse
    {
        $data = $request->validated();
        $created = $service->create($group, (int) $data['user_id'], (int) $data['excluded_user_id'], !empty($data['reciprocal']));
        if ($created === null) {
            return back()->with('flash.error', __('messages.exclusions.impossible'));
        }
        return back()->with('flash.success', __('messages.exclusions.created'));
    }

    public function destroy(Group $group, GroupExclusion $exclusion, ExclusionService $service): RedirectResponse
    {
        if ($group->owner_id !== auth()->id()) {
            abort(403);
        }
        $service->delete($group, $exclusion);
        return back()->with('flash.success', __('messages.exclusions.deleted'));
    }

    /**
     * Preview feasibility of current exclusions.
     */
    public function preview(Group $group, ExclusionService $service, DrawService $drawService): JsonResponse
    {
        if ($group->owner_id !== auth()->id()) {
            abort(403);
        }
        return response()->json($service->preview($group, $drawService));
    }
}

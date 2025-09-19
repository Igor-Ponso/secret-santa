<?php

namespace App\Services;

use App\Models\Group;
use App\Models\GroupExclusion;
use App\Services\DrawService;
use Illuminate\Support\Collection;

/**
 * Service encapsulating exclusion operations & feasibility checks.
 */
class ExclusionService
{
    /**
     * Ensure group exclusions can still be modified (not after draw).
     */
    public function assertModifiable(Group $group): void
    {
        if ($group->has_draw) {
            abort(422, __('messages.exclusions.locked_after_draw'));
        }
    }

    /**
     * Create an exclusion (and optionally reciprocal), returning created models.
     * Performs impossibility rollback if new exclusions make draw impossible.
     *
     * @return Collection<int, GroupExclusion>
     */
    /**
     * @return Collection<int, GroupExclusion>|null Returns created models or null if impossible.
     */
    public function create(Group $group, int $userId, int $excludedUserId, bool $reciprocal = false): ?Collection
    {
        $this->assertModifiable($group);

        $created = collect();
        $rows = [[$userId, $excludedUserId]];
        if ($reciprocal) {
            $rows[] = [$excludedUserId, $userId];
        }

        $newIds = [];
        foreach ($rows as [$u, $e]) {
            $model = GroupExclusion::firstOrCreate([
                'group_id' => $group->id,
                'user_id' => $u,
                'excluded_user_id' => $e,
            ]);
            if ($model->wasRecentlyCreated) {
                $created->push($model);
                $newIds[] = $model->id;
            }
        }

        if ($this->isImpossible($group)) {
            if (!empty($newIds)) {
                GroupExclusion::whereIn('id', $newIds)->delete();
            }
            return null; // signal impossibility to caller
        }

        return $created;
    }

    /**
     * Delete an exclusion if belongs to group.
     */
    public function delete(Group $group, GroupExclusion $exclusion): void
    {
        $this->assertModifiable($group);
        if ($exclusion->group_id !== $group->id) {
            abort(404, __('messages.exclusions.not_found'));
        }
        $exclusion->delete();
    }

    /**
     * Preview feasibility and optionally produce one valid assignment sample (future enhancement placeholder).
     * Returns array: ['feasible' => bool, 'sample' => ?array]
     */
    public function preview(Group $group, ?DrawService $drawService = null): array
    {
        $feasible = !$this->isImpossible($group);
        $sample = null;
        if ($feasible && $drawService) {
            $sample = $drawService->sample($group);
        }
        return [
            'feasible' => $feasible,
            'message' => $feasible ? __('messages.exclusions.preview.feasible') : __('messages.exclusions.preview.infeasible'),
            'sample' => $sample,
        ];
    }

    /**
     * Basic impossibility: some participant excludes everyone else.
     */
    public function isImpossible(Group $group): bool
    {
        $participants = $group->participants()->pluck('users.id')->push($group->owner_id)->unique();
        $total = $participants->count();
        if ($total <= 1) {
            return false; // trivial / no draw scenario handled elsewhere
        }
        $exclusionGroups = $group->exclusions()->get()->groupBy('user_id');
        foreach ($participants as $pid) {
            $count = isset($exclusionGroups[$pid]) ? $exclusionGroups[$pid]->pluck('excluded_user_id')->unique()->count() : 0;
            if ($count >= $total - 1) {
                return true;
            }
        }
        return false;
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Group;

class StoreGroupExclusionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Group|null $group */
        $group = $this->route('group');
        return $group && $this->user() && $group->owner_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id', 'different:excluded_user_id'],
            'excluded_user_id' => ['required', 'integer', 'exists:users,id', 'different:user_id'],
            'reciprocal' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Configure the validator instance with additional contextual checks:
     * - Group must not have completed the draw (lock modifications)
     * - Both user_id and excluded_user_id must be accepted participants (or owner)
     * - Exclusion (or inverse) must not already exist
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            /** @var Group|null $group */
            $group = $this->route('group');
            if (!$group) {
                return; // route binding failure handled elsewhere
            }

            // Lock after draw
            if ($group->has_draw) {
                $v->errors()->add('group', __('messages.exclusions.locked_after_draw'));
                return; // No need to proceed with further checks
            }

            // If base validation failed, skip expensive checks
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $userId = (int) $this->input('user_id');
            $excludedId = (int) $this->input('excluded_user_id');

            // Participant membership (owner + accepted invitations)
            $participantIds = $group->participants()->pluck('users.id')->push($group->owner_id)->unique();
            if (!$participantIds->contains($userId) || !$participantIds->contains($excludedId)) {
                $v->errors()->add('user_id', __('messages.exclusions.invalid_participant'));
                return;
            }

            // Duplicate or inverse existing?
            $existing = $group->exclusions()
                ->where(function ($q) use ($userId, $excludedId) {
                    $q->where(function ($q2) use ($userId, $excludedId) {
                        $q2->where('user_id', $userId)->where('excluded_user_id', $excludedId);
                    })->orWhere(function ($q2) use ($userId, $excludedId) {
                        $q2->where('user_id', $excludedId)->where('excluded_user_id', $userId);
                    });
                })
                ->exists();

            if ($existing) {
                $v->errors()->add('user_id', __('messages.exclusions.duplicate'));
                return;
            }
        });
    }
}

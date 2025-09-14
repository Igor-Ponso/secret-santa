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
        ];
    }
}

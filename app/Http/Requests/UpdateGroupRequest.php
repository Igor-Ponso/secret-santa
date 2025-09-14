<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupRequest extends FormRequest
{
    /**
     * Authorize the request (ownership verified via policy in controller).
     */
    public function authorize(): bool
    {
        return $this->user() !== null; // policy will handle ownership in controller
    }

    /**
     * Validation rules for updating a group.
     *
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:280'],
            'min_value' => ['nullable', 'integer', 'min:0'],
            'max_value' => ['nullable', 'integer', 'min:0', 'gte:min_value'],
            'draw_at' => ['required', 'date', 'after:now'],
        ];
    }
}

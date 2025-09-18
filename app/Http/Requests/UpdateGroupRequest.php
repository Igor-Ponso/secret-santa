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
            'min_gift_cents' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'max_gift_cents' => ['nullable', 'integer', 'min:0', 'max:10000000', 'gte:min_gift_cents'],
            'currency' => ['nullable', 'string', 'size:3'],
            'draw_at' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }
}

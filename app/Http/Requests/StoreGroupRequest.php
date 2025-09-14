<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupRequest extends FormRequest
{
    /**
     * Authorize the request (authentication handled by middleware).
     */
    public function authorize(): bool
    {
        return $this->user() !== null; // auth middleware already enforced
    }

    /**
     * Validation rules for creating a group.
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
            'draw_at' => ['required', 'date', 'after:now'],
        ];
    }
}

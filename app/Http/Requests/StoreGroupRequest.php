<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // auth middleware already enforced
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:280'],
            'min_value' => ['nullable', 'integer', 'min:0'],
            'max_value' => ['nullable', 'integer', 'min:0', 'gte:min_value'],
            'draw_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}

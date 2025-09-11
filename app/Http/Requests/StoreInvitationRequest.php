<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvitationRequest extends FormRequest
{
    /**
     * Authorize request (policy check happens at controller level).
     */
    public function authorize(): bool
    {
        return true; // Policy applied in controller
    }

    /**
     * Validation rules for creating an invitation.
     *
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc,dns', 'max:255'],
        ];
    }
}

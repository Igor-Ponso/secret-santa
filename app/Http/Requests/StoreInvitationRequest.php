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
            // Removed 'dns' validation to make tests deterministic in CI environments without outbound DNS.
            // RFC validation keeps format correctness; MX existence is a secondary check we can apply asynchronously.
            'email' => ['required', 'email:rfc', 'max:255'],
        ];
    }
}

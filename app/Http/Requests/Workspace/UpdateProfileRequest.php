<?php

declare(strict_types=1);

namespace App\Http\Requests\Workspace;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Self-service profile fields. Email and role stay with the Hub Director.
 */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'notify_new_grants' => $this->boolean('notify_new_grants'),
            'notify_contact_messages' => $this->boolean('notify_contact_messages'),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'notify_new_grants' => ['boolean'],
            'notify_contact_messages' => ['boolean'],
        ];
    }
}

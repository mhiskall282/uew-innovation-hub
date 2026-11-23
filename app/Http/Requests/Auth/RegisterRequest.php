<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /** Refuse outright (403) while the Hub Director has paused sign-ups. */
    public function authorize(): bool
    {
        return app(\App\Services\SettingsService::class)->bool('registration_open');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
            'company_website' => ['nullable', 'max:0'], // honeypot
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.unique' => 'An account with this e-mail already exists. Sign in or reset your password.',
            'terms.accepted' => 'Please agree to the terms and privacy notice to continue.',
            'company_website.max' => 'Your sign-up could not be processed.',
        ];
    }
}

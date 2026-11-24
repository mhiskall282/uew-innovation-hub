<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Services\SettingsService;
use App\Support\Demographics;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /** Refuse outright (403) while the Hub Director has paused sign-ups. */
    public function authorize(): bool
    {
        return app(SettingsService::class)->bool('registration_open');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'name' => preg_replace('/\s+/', ' ', trim((string) $this->input('name'))),
            'phone' => trim((string) $this->input('phone')),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9()\-\s]{7,30}$/'],
            'gender' => ['required', Rule::in(array_keys(Demographics::GENDERS))],
            'date_of_birth' => ['required', 'date', 'before_or_equal:'.now()->subYears(Demographics::MIN_AGE)->toDateString(), 'after:1920-01-01'],
            'region' => ['required', Rule::in(Demographics::REGIONS)],
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
            'phone.regex' => 'Enter a valid phone number, for example +233 24 123 4567.',
            'date_of_birth.before_or_equal' => 'You need to be at least '.Demographics::MIN_AGE.' years old to create an account.',
            'terms.accepted' => 'Please agree to the terms and privacy notice to continue.',
            'company_website.max' => 'Your sign-up could not be processed.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['date_of_birth' => 'date of birth', 'phone' => 'phone number'];
    }

    /** @return array<string, mixed> */
    public function accountData(): array
    {
        return $this->safe()->only(['name', 'email', 'phone', 'gender', 'date_of_birth', 'region', 'password']);
    }
}

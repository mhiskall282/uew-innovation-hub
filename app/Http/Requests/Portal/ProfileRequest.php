<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal;

use App\Enums\Affiliation;
use App\Enums\ProgramType;
use App\Support\Demographics;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Member profile, used by onboarding and by "Profile & settings". */
class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'marketing_opt_in' => $this->boolean('marketing_opt_in'),
            'public_profile' => $this->boolean('public_profile'),
            'phone' => trim((string) $this->input('phone')),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9()\-\s]{7,30}$/'],
            'gender' => ['required', Rule::in(array_keys(Demographics::GENDERS))],
            'date_of_birth' => ['required', 'date', 'before_or_equal:'.now()->subYears(Demographics::MIN_AGE)->toDateString(), 'after:1920-01-01'],
            'region' => ['required', Rule::in(Demographics::REGIONS)],
            'affiliation' => ['required', Rule::enum(Affiliation::class)],
            'institution' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'education_level' => ['required', Rule::in(array_keys(Demographics::EDUCATION_LEVELS))],
            'disability' => ['nullable', Rule::in(array_keys(Demographics::DISABILITY))],
            'bio' => ['nullable', 'string', 'max:1000'],
            'interests' => ['required', 'array', 'min:1', 'max:12'],
            'interests.*' => ['string', Rule::in(self::interestOptions())],
            'marketing_opt_in' => ['boolean'],
            'public_profile' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid phone number, for example +233 24 123 4567.',
            'date_of_birth.before_or_equal' => 'You need to be at least '.Demographics::MIN_AGE.' years old.',
            'interests.required' => 'Choose at least one area you are interested in.',
            'interests.min' => 'Choose at least one area you are interested in.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['date_of_birth' => 'date of birth', 'education_level' => 'education level', 'affiliation' => 'affiliation'];
    }

    /** Focus areas plus programme types members can follow. */
    public static function interestOptions(): array
    {
        return array_merge(
            config('hub.focus_areas'),
            array_map(fn (ProgramType $t) => $t->plural(), ProgramType::cases()),
        );
    }
}

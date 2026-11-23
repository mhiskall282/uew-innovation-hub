<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal;

use App\Enums\Affiliation;
use App\Enums\ProgramType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Member profile, used by onboarding and by "My profile". */
class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['marketing_opt_in' => $this->boolean('marketing_opt_in')]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{7,30}$/'],
            'affiliation' => ['required', Rule::enum(Affiliation::class)],
            'institution' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'interests' => ['required', 'array', 'min:1', 'max:12'],
            'interests.*' => ['string', Rule::in(self::interestOptions())],
            'marketing_opt_in' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid phone number.',
            'interests.required' => 'Choose at least one area you are interested in.',
            'interests.min' => 'Choose at least one area you are interested in.',
        ];
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

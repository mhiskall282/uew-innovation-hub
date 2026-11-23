<?php

declare(strict_types=1);

namespace App\Http\Requests\Workspace;

use App\Enums\GrantStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewGrantApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['notify_applicant' => $this->boolean('notify_applicant')]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(GrantStatus::class)],
            'reviewer_notes' => ['nullable', 'string', 'max:5000', 'required_if:status,rejected'],
            'notify_applicant' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['reviewer_notes.required_if' => 'Add a note explaining the decision before declining an application.'];
    }
}

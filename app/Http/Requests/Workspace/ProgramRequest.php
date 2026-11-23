<?php

declare(strict_types=1);

namespace App\Http\Requests\Workspace;

use App\Enums\ProgramStatus;
use App\Enums\ProgramType;
use App\Support\FormSchema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // routes are gated by can:programs.manage
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_featured' => $this->boolean('is_featured'),
            'notify_members' => $this->boolean('notify_members'),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'type' => ['required', Rule::enum(ProgramType::class)],
            'focus_area' => ['nullable', 'string', Rule::in(config('hub.focus_areas'))],
            'status' => ['required', Rule::enum(ProgramStatus::class)],
            'summary' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:50000'],
            'eligibility' => ['nullable', 'string', 'max:5000'],
            'funding_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'location' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after:opens_at'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'is_featured' => ['boolean'],
            'notify_members' => ['boolean'],
            'fields' => ['nullable', 'array', 'max:'.FormSchema::MAX_FIELDS],
            'fields.*.key' => ['nullable', 'string', 'max:40'],
            'fields.*.label' => ['required', 'string', 'max:200'],
            'fields.*.type' => ['required', Rule::in(array_keys(FormSchema::TYPES))],
            'fields.*.required' => ['nullable'],
            'fields.*.help' => ['nullable', 'string', 'max:300'],
            'fields.*.options' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'closes_at' => 'closing date',
            'opens_at' => 'opening date',
            'fields.*.label' => 'question',
            'fields.*.type' => 'answer type',
        ];
    }
}

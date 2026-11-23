<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->canManageProjects();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
            'category' => ['required', 'string', 'max:100'],
            'status' => ['required', 'in:active,completed,suspended,pending'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'beneficiaries_target' => ['nullable', 'integer', 'min:0'],
            'beneficiaries_reached' => ['nullable', 'integer', 'min:0'],
            'grant_amount' => ['nullable', 'numeric', 'min:0'],
            'lead_researcher' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'report_file' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'organizations' => ['nullable', 'array'],
        ];
    }
}

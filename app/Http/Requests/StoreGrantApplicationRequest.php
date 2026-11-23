<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGrantApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public submission form
    }

    public function rules(): array
    {
        return [
            'applicant_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'institution_or_org' => ['nullable', 'string', 'max:255'],
            'project_title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'funding_requested' => ['required', 'numeric', 'min:100', 'max:1000000'],
            'summary' => ['required', 'string', 'min:20'],
            'proposal_file' => ['nullable', 'file', 'mimes:pdf', 'max:20480'], // max 20MB
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Workspace;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['remove_document' => $this->boolean('remove_document')]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(config('hub.publication_types'))],
            'authors' => ['nullable', 'string', 'max:500'],
            'abstract' => ['nullable', 'string', 'max:5000'],
            'publish_date' => ['nullable', 'date'],
            'journal_name' => ['nullable', 'string', 'max:255'],
            'doi_url' => ['nullable', 'url:https,http', 'max:500'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'document' => ['nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:'.config('hub.max_upload_kb')],
            'remove_document' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['doi_url' => 'DOI or link', 'project_id' => 'related project', 'document' => 'PDF'];
    }
}

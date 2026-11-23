<?php

declare(strict_types=1);

namespace App\Http\Requests\Workspace;

use App\Enums\PostType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_featured' => $this->boolean('is_featured')]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(PostType::class)],
            'category' => ['required', 'string', Rule::in(config('hub.post_categories'))],
            'summary' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string', 'max:50000'],
            'is_featured' => ['boolean'],
            'publish_action' => ['required', Rule::in(['draft', 'publish', 'schedule'])],
            // Only meaningful when scheduling; ignored (and stripped) otherwise.
            'published_at' => ['exclude_unless:publish_action,schedule', 'required', 'date', 'after:now'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'published_at.required' => 'Choose a date and time to schedule the post.',
            'published_at.after' => 'The scheduled time must be in the future.',
        ];
    }
}

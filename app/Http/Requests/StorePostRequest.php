<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->canManagePosts();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:news,blog,announcement'],
            'category' => ['required', 'string', 'max:100'],
            'summary' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'is_featured' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'cover_image' => ['nullable', 'image', 'max:5120'], // max 5MB
        ];
    }
}

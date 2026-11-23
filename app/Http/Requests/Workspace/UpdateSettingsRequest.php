<?php

declare(strict_types=1);

namespace App\Http\Requests\Workspace;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by can:settings.manage on the route
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'announcement_enabled' => $this->boolean('announcement_enabled'),
            'registration_open' => $this->boolean('registration_open'),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $url = ['nullable', 'url:https,http', 'max:255'];

        return [
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'address' => ['required', 'string', 'max:255'],
            'office_hours' => ['nullable', 'string', 'max:120'],
            'map_url' => $url,
            'social_facebook' => $url,
            'social_x' => $url,
            'social_linkedin' => $url,
            'social_youtube' => $url,
            'social_instagram' => $url,
            'hero_eyebrow' => ['required', 'string', 'max:80'],
            'hero_title' => ['required', 'string', 'max:140'],
            'hero_subtitle' => ['required', 'string', 'max:400'],
            'cta_title' => ['required', 'string', 'max:140'],
            'cta_text' => ['required', 'string', 'max:400'],
            'footer_about' => ['required', 'string', 'max:400'],
            'announcement_enabled' => ['boolean'],
            'announcement_text' => ['nullable', 'required_if:announcement_enabled,true', 'string', 'max:200'],
            'announcement_url' => $url,
            'registration_open' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['announcement_text.required_if' => 'Write the announcement text, or switch the banner off.'];
    }
}

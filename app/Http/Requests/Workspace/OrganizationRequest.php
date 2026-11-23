<?php

declare(strict_types=1);

namespace App\Http\Requests\Workspace;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $organization = $this->route('organization');

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('organizations', 'name')->ignore($organization instanceof Organization ? $organization->id : null),
            ],
            'type' => ['required', 'string', Rule::in(config('hub.organization_types'))],
            'website' => ['nullable', 'url:https,http', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ];
    }
}

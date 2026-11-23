<?php

declare(strict_types=1);

namespace App\Http\Requests\Workspace;

use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Used for both create and update. Authorization is handled by ProjectPolicy
 * in the controller.
 */
class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_public' => $this->boolean('is_public'),
            'remove_report' => $this->boolean('remove_report'),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', 'string', Rule::in(config('hub.focus_areas'))],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'beneficiaries_target' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'beneficiaries_reached' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'grant_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'lead_researcher' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'is_public' => ['boolean'],
            'report_file' => ['nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:'.config('hub.max_upload_kb')],
            'remove_report' => ['boolean'],
            'partners' => ['nullable', 'array', 'max:50'],
            'partners.*.organization_id' => ['nullable', 'integer', 'distinct', 'exists:organizations,id'],
            'partners.*.role' => ['nullable', 'string', Rule::in(config('hub.partner_roles'))],
        ];
    }

    /** @return array<string, mixed> */
    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);

        if ($key === null) {
            $data['beneficiaries_target'] = (int) ($data['beneficiaries_target'] ?? 0);
            $data['beneficiaries_reached'] = (int) ($data['beneficiaries_reached'] ?? 0);
        }

        return $data;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'category' => 'focus area',
            'report_file' => 'report',
            'partners.*.organization_id' => 'partner',
            'partners.*.role' => 'partner role',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal;

use App\Models\ProgressReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Progress and budget report from an accepted applicant. Ownership is checked by policy in the controller. */
class ProgressReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        // Drop the empty rows the form always shows at the bottom.
        $items = array_values(array_filter((array) $this->input('line_items', []), fn ($row) => is_array($row)
            && (trim((string) ($row['description'] ?? '')) !== '' || (float) ($row['budgeted'] ?? 0) > 0 || (float) ($row['spent'] ?? 0) > 0)));

        $this->merge(['line_items' => $items]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'period' => ['required', 'string', 'max:60'],
            'period_end' => ['required', 'date', 'before_or_equal:'.now()->addMonth()->toDateString(), 'after:2015-01-01'],
            'summary' => ['required', 'string', 'min:20', 'max:5000'],
            'milestones' => ['required', 'string', 'max:5000'],
            'challenges' => ['nullable', 'string', 'max:5000'],
            'next_steps' => ['nullable', 'string', 'max:5000'],
            'beneficiaries' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'budget_received' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'budget_spent' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'line_items' => ['array', 'max:30'],
            'line_items.*.category' => ['required', Rule::in(ProgressReport::CATEGORIES)],
            'line_items.*.description' => ['required', 'string', 'max:200'],
            'line_items.*.budgeted' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'line_items.*.spent' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'period_end' => 'end of the reporting period',
            'budget_received' => 'funds received',
            'budget_spent' => 'funds spent',
            'line_items.*.category' => 'category',
            'line_items.*.description' => 'item description',
            'line_items.*.budgeted' => 'budgeted amount',
            'line_items.*.spent' => 'amount spent',
        ];
    }
}

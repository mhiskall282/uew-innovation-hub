<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Custom application forms built by staff for each programme.
 *
 * A schema is a list of fields:
 *   ['key' => 'team_size', 'label' => 'Team size', 'type' => 'number',
 *    'required' => true, 'help' => '...', 'options' => ['A', 'B']]
 *
 * Keys are stable once created so existing answers keep their meaning when
 * staff rename a label. Member answers are validated against the schema
 * server-side; the browser form is only a convenience.
 */
final class FormSchema
{
    public const TYPES = [
        'short_text' => 'Short answer',
        'long_text' => 'Paragraph',
        'number' => 'Number',
        'email' => 'Email address',
        'phone' => 'Phone number',
        'url' => 'Website link',
        'date' => 'Date',
        'select' => 'Dropdown (one choice)',
        'radio' => 'Multiple choice (one)',
        'checkboxes' => 'Checkboxes (several)',
        'yes_no' => 'Yes / No',
        'file' => 'PDF upload',
    ];

    public const WITH_OPTIONS = ['select', 'radio', 'checkboxes'];

    public const MAX_FIELDS = 40;

    /**
     * Clean up a schema submitted by the form builder.
     *
     * @param  array<int, array<string, mixed>>  $fields
     * @return list<array{key: string, label: string, type: string, required: bool, help: ?string, options: list<string>}>
     */
    public static function normalize(array $fields): array
    {
        $used = [];
        $clean = [];

        foreach (array_slice(array_values($fields), 0, self::MAX_FIELDS) as $field) {
            $label = trim((string) ($field['label'] ?? ''));
            $type = (string) ($field['type'] ?? 'short_text');

            if ($label === '' || ! array_key_exists($type, self::TYPES)) {
                continue;
            }

            $key = Str::slug((string) ($field['key'] ?? '') ?: $label, '_') ?: 'field';
            $key = Str::limit($key, 40, '');
            $base = $key;
            $n = 2;
            while (in_array($key, $used, true)) {
                $key = "{$base}_{$n}";
                $n++;
            }
            $used[] = $key;

            $options = [];
            if (in_array($type, self::WITH_OPTIONS, true)) {
                $raw = is_array($field['options'] ?? null) ? $field['options'] : preg_split('/\r\n|\r|\n/', (string) ($field['options'] ?? ''));
                $options = array_values(array_unique(array_filter(array_map(fn ($o) => Str::limit(trim((string) $o), 120, ''), $raw), 'strlen')));
                if ($options === []) {
                    continue; // a choice field with no choices is unusable
                }
            }

            $clean[] = [
                'key' => $key,
                'label' => Str::limit($label, 200, ''),
                'type' => $type,
                'required' => filter_var($field['required'] ?? false, FILTER_VALIDATE_BOOL),
                'help' => ($help = trim((string) ($field['help'] ?? ''))) !== '' ? Str::limit($help, 300, '') : null,
                'options' => $options,
            ];
        }

        return $clean;
    }

    /**
     * Validation rules for answers. Drafts skip "required" so members can save
     * partial work; submission enforces it.
     *
     * @param  list<array<string, mixed>>  $fields
     * @param  array<string, mixed>  $existing  answers already stored (for files already uploaded)
     * @return array<string, mixed>
     */
    public static function rules(array $fields, bool $submitting, array $existing = []): array
    {
        $rules = [];

        foreach ($fields as $field) {
            $key = $field['key'];
            $required = $submitting && $field['required'];

            if ($field['type'] === 'file') {
                $hasFile = ! empty($existing[$key]);
                $rules["files.{$key}"] = array_filter([
                    $required && ! $hasFile ? 'required' : 'nullable',
                    'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:'.config('hub.max_upload_kb'),
                ]);

                continue;
            }

            $base = $required ? ['required'] : ['nullable'];

            $rules["answers.{$key}"] = match ($field['type']) {
                'short_text' => [...$base, 'string', 'max:255'],
                'long_text' => [...$base, 'string', 'max:10000'],
                'number' => [...$base, 'numeric', 'min:0', 'max:1000000000'],
                'email' => [...$base, 'email', 'max:255'],
                'phone' => [...$base, 'string', 'max:30', 'regex:/^[0-9+()\-\s]{7,30}$/'],
                'url' => [...$base, 'url:https,http', 'max:500'],
                'date' => [...$base, 'date'],
                'select', 'radio' => [...$base, 'string', Rule::in($field['options'])],
                'checkboxes' => [...$base, 'array'],
                'yes_no' => [...$base, Rule::in(['yes', 'no'])],
                default => [...$base, 'string', 'max:255'],
            };

            if ($field['type'] === 'checkboxes') {
                $rules["answers.{$key}.*"] = ['string', Rule::in($field['options'])];
            }
        }

        return $rules;
    }

    /**
     * Readable attribute names for validation messages.
     *
     * @param  list<array<string, mixed>>  $fields
     * @return array<string, string>
     */
    public static function attributes(array $fields): array
    {
        $names = [];
        foreach ($fields as $field) {
            $prefix = $field['type'] === 'file' ? 'files' : 'answers';
            $names["{$prefix}.{$field['key']}"] = $field['label'];
        }

        return $names;
    }

    /** Display value for an answer in reviews, exports and e-mails. */
    public static function display(array $field, mixed $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '—';
        }

        return match ($field['type']) {
            'checkboxes' => implode(', ', (array) $value),
            'yes_no' => $value === 'yes' ? 'Yes' : 'No',
            'file' => 'PDF attached',
            default => (string) $value,
        };
    }

    /** A starter form so new programmes are usable immediately. */
    public static function starter(): array
    {
        return self::normalize([
            ['key' => 'title', 'label' => 'Project or idea title', 'type' => 'short_text', 'required' => true],
            ['key' => 'description', 'label' => 'Describe your idea and the problem it solves', 'type' => 'long_text', 'required' => true, 'help' => 'Who benefits, and how will you deliver it?'],
            ['key' => 'team_size', 'label' => 'Team size', 'type' => 'number', 'required' => false],
            ['key' => 'document', 'label' => 'Supporting document', 'type' => 'file', 'required' => false, 'help' => 'Optional proposal or CV as a PDF.'],
        ]);
    }
}

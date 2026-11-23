<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ProgramStatus;
use App\Models\Broadcast;
use App\Models\Program;
use App\Models\User;
use App\Support\EmailTemplates;
use App\Support\FormSchema;
use Illuminate\Support\Facades\DB;

/** Programme lifecycle: draft → published → closed → archived. */
final class ProgramService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly BroadcastService $broadcasts,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated programme fields incl. `fields` (form builder) and `status`
     */
    public function create(array $data, User $actor): Program
    {
        return DB::transaction(function () use ($data, $actor) {
            $program = new Program($this->attributes($data));
            $program->created_by = $actor->id;
            $this->applyStatus($program, ProgramStatus::from($data['status'] ?? 'draft'));
            $program->save();

            $this->audit->record('program.created', "Created programme \"{$program->title}\" ({$program->status->label()})", $program);

            return $program;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Program $program, array $data): Program
    {
        return DB::transaction(function () use ($program, $data) {
            $program->fill($this->attributes($data));
            $this->applyStatus($program, ProgramStatus::from($data['status'] ?? $program->status->value));

            $changes = $this->audit->diff($program);
            unset($changes['description'], $changes['form_schema']);
            if ($program->isDirty('form_schema')) {
                $changes['form'] = 'changed';
            }
            $program->save();

            $this->audit->record('program.updated', "Updated programme \"{$program->title}\"", $program, $changes);

            return $program;
        });
    }

    public function delete(Program $program): void
    {
        $program->delete();
        $this->audit->record('program.deleted', "Deleted programme \"{$program->title}\"", $program);
    }

    /**
     * Announce a published programme to opted-in members whose interests
     * include its focus area (or everyone opted in when it has none).
     */
    public function announce(Program $program, User $actor): Broadcast
    {
        $content = EmailTemplates::content('program_launched');
        $values = [
            'program' => $program->title,
            'program_type' => strtolower($program->type->label()),
            'summary' => (string) $program->summary,
            'deadline' => $program->closes_at?->format('j F Y, H:i') ?? 'Open until further notice',
            'site_name' => config('hub.name'),
        ];

        // Pre-fill programme details; leave recipient placeholders for the sender.
        $fill = fn (string $text) => preg_replace_callback('/\{\{\s*([a-z_]+)\s*\}\}/', fn ($m) => array_key_exists($m[1], $values) ? $values[$m[1]] : $m[0], $text);

        $broadcast = new Broadcast([
            'subject' => $fill($content['subject']),
            'body' => $fill($content['body']),
            'audience' => ['interests' => array_filter([$program->focus_area]), 'affiliations' => []],
            'program_id' => $program->id,
        ]);
        $broadcast->created_by = $actor->id;
        $broadcast->save();

        $this->broadcasts->send($broadcast);

        return $broadcast;
    }

    /** @param array<string, mixed> $data */
    private function attributes(array $data): array
    {
        $attributes = collect($data)->only([
            'title', 'type', 'focus_area', 'summary', 'description', 'eligibility', 'funding_amount',
            'location', 'starts_at', 'opens_at', 'closes_at', 'capacity', 'is_featured',
        ])->all();

        if (array_key_exists('fields', $data)) {
            $attributes['form_schema'] = FormSchema::normalize($data['fields'] ?? []);
        }

        return $attributes;
    }

    private function applyStatus(Program $program, ProgramStatus $status): void
    {
        $program->status = $status;

        if ($status === ProgramStatus::Published && $program->published_at === null) {
            $program->published_at = now();
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class ProjectService
{
    public function __construct(
        private readonly DocumentStorage $documents,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated attributes, may include `partners` and `report_file`
     */
    public function create(array $data, User $actor): Project
    {
        return DB::transaction(function () use ($data, $actor) {
            [$attributes, $partners, $file] = $this->split($data);

            $project = new Project($attributes);
            $project->created_by = $actor->id;

            if ($file) {
                $project->report_file_path = $this->documents->store($file, DocumentStorage::REPORTS, 'project');
            }

            $project->save();
            $project->organizations()->sync($partners);

            $this->audit->record('project.created', "Registered project \"{$project->title}\"", $project);

            return $project;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Project $project, array $data): Project
    {
        return DB::transaction(function () use ($project, $data) {
            [$attributes, $partners, $file] = $this->split($data);

            $project->fill($attributes);

            if ($file) {
                $project->report_file_path = $this->documents->replace(
                    $project->report_file_path, $file, DocumentStorage::REPORTS, 'project'
                );
            } elseif (! empty($data['remove_report']) && $project->report_file_path) {
                $this->documents->delete($project->report_file_path, DocumentStorage::REPORTS);
                $project->report_file_path = null;
            }

            $changes = $this->audit->diff($project);
            $project->save();
            $partnerChanges = $project->organizations()->sync($partners);

            if (array_filter($partnerChanges)) {
                $changes['partners'] = $partnerChanges;
            }

            $this->audit->record('project.updated', "Updated project \"{$project->title}\"", $project, $changes);

            return $project;
        });
    }

    /** Soft delete. The report file is kept so the record can be restored. */
    public function delete(Project $project): void
    {
        DB::transaction(function () use ($project) {
            $project->delete();
            $this->audit->record('project.deleted', "Archived project \"{$project->title}\"", $project);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: array<int, array{role: string}>, 2: ?UploadedFile}
     */
    private function split(array $data): array
    {
        $partners = collect($data['partners'] ?? [])
            ->filter(fn ($row) => ! empty($row['organization_id']))
            ->mapWithKeys(fn ($row) => [(int) $row['organization_id'] => ['role' => $row['role'] ?? 'Partner']])
            ->all();

        $file = ($data['report_file'] ?? null) instanceof UploadedFile ? $data['report_file'] : null;

        $attributes = collect($data)->except(['partners', 'report_file', 'remove_report'])->all();

        return [$attributes, $partners, $file];
    }
}

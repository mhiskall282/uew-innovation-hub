<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Support\HubStats;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProjectService
{
    public function getDashboardMetrics(): array
    {
        return Cache::remember('hub.dashboard_metrics', HubStats::TTL, function () {
            $totals = Project::query()
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active")
                ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
                ->selectRaw('COALESCE(SUM(beneficiaries_reached), 0) as reached')
                ->selectRaw('COALESCE(SUM(beneficiaries_target), 0) as target')
                ->selectRaw('COALESCE(SUM(grant_amount), 0) as funding')
                ->toBase()
                ->first();

            $byCategory = Project::query()
                ->select('category', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('category')
                ->pluck('aggregate', 'category')
                ->toArray();

            $yearExpr = Project::yearExpression();
            $byYear = Project::query()
                ->select(DB::raw("{$yearExpr} as year"), DB::raw('COUNT(*) as aggregate'))
                ->whereNotNull('start_date')
                ->groupBy(DB::raw($yearExpr))
                ->orderByDesc(DB::raw($yearExpr))
                ->pluck('aggregate', 'year')
                ->toArray();

            return [
                'total_projects' => (int) $totals->total,
                'active_projects' => (int) $totals->active,
                'completed_projects' => (int) $totals->completed,
                'total_beneficiaries' => (int) $totals->reached,
                'total_target_beneficiaries' => (int) $totals->target,
                'total_funding' => (float) $totals->funding,
                'total_organizations' => Organization::count(),
                'by_category' => $byCategory,
                'by_year' => $byYear,
            ];
        });
    }
    public function getFilteredPaginatedProjects(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Project::with(['organizations', 'creator'])
            ->filter($filters)
            ->latest('start_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createProject(array $data, ?User $user = null): Project
    {
        return DB::transaction(function () use ($data, $user) {
            $organizations = $data['organizations'] ?? [];
            unset($data['organizations']);

            if (isset($data['report_file']) && $data['report_file']->isValid()) {
                $filename = 'project_' . time() . '_' . Str::lower(Str::random(8)) . '.pdf';
                $data['report_file']->storeAs('reports', $filename, 'private');
                $data['report_file_path'] = $filename;
                unset($data['report_file']);
            }

            if ($user) {
                $data['created_by'] = $user->id;
            }

            $project = Project::create($data);

            if (!empty($organizations)) {
                $syncData = [];
                foreach ($organizations as $orgId => $role) {
                    if (!empty($role)) {
                        $syncData[$orgId] = ['role' => is_string($role) ? $role : 'Partner'];
                    }
                }
                $project->organizations()->sync($syncData);
            }

            ActivityLog::create([
                'user_id' => $user?->id,
                'action' => 'project.created',
                'description' => "Registered new project: {$project->title}",
                'ip_address' => request()->ip(),
            ]);

            HubStats::flush();

            return $project;
        });
    }

    public function updateProject(Project $project, array $data, ?User $user = null): Project
    {
        return DB::transaction(function () use ($project, $data, $user) {
            $organizations = $data['organizations'] ?? null;
            unset($data['organizations']);

            if (isset($data['report_file']) && $data['report_file']->isValid()) {
                // Remove old file if exists
                if ($project->report_file_path) {
                    Storage::disk('private')->delete('reports/' . $project->report_file_path);
                }

                $filename = 'project_' . $project->id . '_' . time() . '.pdf';
                $data['report_file']->storeAs('reports', $filename, 'private');
                $data['report_file_path'] = $filename;
                unset($data['report_file']);
            }

            $project->update($data);

            if ($organizations !== null) {
                $syncData = [];
                foreach ($organizations as $orgId => $role) {
                    if (!empty($role)) {
                        $syncData[$orgId] = ['role' => is_string($role) ? $role : 'Partner'];
                    }
                }
                $project->organizations()->sync($syncData);
            }

            ActivityLog::create([
                'user_id' => $user?->id,
                'action' => 'project.updated',
                'description' => "Updated project attributes for: {$project->title}",
                'ip_address' => request()->ip(),
            ]);

            HubStats::flush();

            return $project;
        });
    }

    public function deleteProject(Project $project, ?User $user = null): bool
    {
        return DB::transaction(function () use ($project, $user) {
            $title = $project->title;
            $deleted = $project->delete();

            ActivityLog::create([
                'user_id' => $user?->id,
                'action' => 'project.deleted',
                'description' => "Soft-deleted project: {$title}",
                'ip_address' => request()->ip(),
            ]);

            HubStats::flush();

            return $deleted;
        });
    }
}

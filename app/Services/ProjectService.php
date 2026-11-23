<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProjectService
{
    public function getDashboardMetrics(): array
    {
        $totalProjects = Project::count();
        $activeProjects = Project::where('status', 'active')->count();
        $completedProjects = Project::where('status', 'completed')->count();
        $totalBeneficiaries = (int) Project::sum('beneficiaries_reached');
        $totalTargetBeneficiaries = (int) Project::sum('beneficiaries_target');
        $totalFunding = (float) Project::sum('grant_amount');
        $totalOrganizations = Organization::count();

        // Projects by Category
        $byCategory = Project::select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        // Projects by Year
        $byYear = Project::select(DB::raw("strftime('%Y', start_date) as year"), DB::raw('count(*) as count'))
            ->whereNotNull('start_date')
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->pluck('count', 'year')
            ->toArray();

        return [
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'completed_projects' => $completedProjects,
            'total_beneficiaries' => $totalBeneficiaries,
            'total_target_beneficiaries' => $totalTargetBeneficiaries,
            'total_funding' => $totalFunding,
            'total_organizations' => $totalOrganizations,
            'by_category' => $byCategory,
            'by_year' => $byYear,
        ];
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
                $filename = 'project_' . time() . '_' . $data['report_file']->getClientOriginalName();
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

            return $deleted;
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramApplication;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $interests = $user->interests ?? [];

        $applications = ProgramApplication::query()
            ->where('user_id', $user->id)
            ->with('program:id,title,slug,type,closes_at,status')
            ->latest('updated_at')
            ->get();

        $appliedIds = $applications->pluck('program_id');

        // Open programmes, those matching the member's interests first.
        $recommended = Program::open()
            ->whereNotIn('id', $appliedIds)
            ->orderByRaw('CASE WHEN focus_area IN ('.implode(',', array_fill(0, max(1, count($interests)), '?')).') THEN 0 ELSE 1 END', $interests ?: [''])
            ->orderByRaw('CASE WHEN closes_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('closes_at')
            ->take(4)
            ->get();

        return view('portal.dashboard', [
            'user' => $user,
            'applications' => $applications,
            'drafts' => $applications->where('status', ApplicationStatus::Draft),
            'recommended' => $recommended,
            'counts' => [
                'active' => $applications->whereIn('status', ApplicationStatus::open())->count(),
                'accepted' => $applications->where('status', ApplicationStatus::Approved)->count(),
                'drafts' => $applications->where('status', ApplicationStatus::Draft)->count(),
            ],
        ]);
    }
}

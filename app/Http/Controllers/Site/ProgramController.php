<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Enums\ProgramType;
use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramApplication;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(Request $request): View
    {
        $type = ProgramType::tryFrom((string) $request->query('type'));
        $tab = in_array($request->query('tab'), ['open', 'upcoming', 'closed'], true) ? $request->query('tab') : 'open';

        $base = fn () => Program::query()
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($request->query('area'), fn ($q, $area) => $q->where('focus_area', $area))
            ->withCount(['applications as applicants_count' => fn ($q) => $q->where('status', '!=', 'draft')]);

        $programs = match ($tab) {
            'upcoming' => $base()->upcoming()->orderBy('opens_at'),
            'closed' => $base()->closedForApplications()->latest('closes_at'),
            default => $base()->open()->orderByDesc('is_featured')->orderByRaw('CASE WHEN closes_at IS NULL THEN 1 ELSE 0 END')->orderBy('closes_at'),
        };

        return view('site.programs.index', [
            'programs' => $programs->paginate(12)->withQueryString(),
            'tab' => $tab,
            'type' => $type,
            'types' => ProgramType::cases(),
            'counts' => [
                'open' => Program::open()->count(),
                'upcoming' => Program::upcoming()->count(),
                'closed' => Program::closedForApplications()->count(),
            ],
            'events' => $tab === 'open' && ! $type
                ? Program::open()->where('type', ProgramType::Event)->orderBy('starts_at')->take(3)->get()
                : collect(),
        ]);
    }

    public function show(Request $request, Program $program): View
    {
        abort_unless($program->isVisible(), 404);

        $application = $request->user()
            ? ProgramApplication::where('program_id', $program->id)->where('user_id', $request->user()->id)->first()
            : null;

        return view('site.programs.show', [
            'program' => $program,
            'application' => $application,
            'related' => Program::open()->whereKeyNot($program->id)->where('type', $program->type)->take(3)->get(),
        ]);
    }
}

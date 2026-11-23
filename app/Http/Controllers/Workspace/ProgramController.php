<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Enums\ApplicationStatus;
use App\Enums\ProgramStatus;
use App\Enums\ProgramType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\ProgramRequest;
use App\Models\Program;
use App\Models\ProgramApplication;
use App\Services\ApplicationService;
use App\Services\ProgramService;
use App\Support\FormSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Programme management. Routes are gated by can:programs.manage (show also by applications.review). */
class ProgramController extends Controller
{
    public function __construct(
        private readonly ProgramService $programs,
        private readonly ApplicationService $applications,
    ) {}

    public function index(Request $request): View
    {
        $status = ProgramStatus::tryFrom((string) $request->query('status'));
        $type = ProgramType::tryFrom((string) $request->query('type'));

        return view('workspace.programs.index', [
            'programs' => Program::query()
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($type, fn ($q) => $q->where('type', $type))
                ->search($request->query('search'), ['title', 'summary'])
                ->withCount([
                    'applications as submitted_count' => fn ($q) => $q->whereNotIn('status', [ApplicationStatus::Draft, ApplicationStatus::Withdrawn]),
                    'applications as pending_count' => fn ($q) => $q->whereIn('status', ApplicationStatus::open()),
                ])
                ->latest('updated_at')
                ->paginate(15)
                ->withQueryString(),
            'statuses' => ProgramStatus::cases(),
            'types' => ProgramType::cases(),
            'filters' => ['status' => $status?->value, 'type' => $type?->value, 'search' => $request->query('search')],
        ]);
    }

    public function create(): View
    {
        $program = new Program(['type' => ProgramType::Grant, 'form_schema' => FormSchema::starter()]);
        $program->status = ProgramStatus::Draft;

        return view('workspace.programs.create', $this->formData($program));
    }

    public function store(ProgramRequest $request): RedirectResponse
    {
        $program = $this->programs->create($request->validated(), $request->user());

        return $this->afterSave($request, $program, 'Programme created.');
    }

    public function show(Request $request, Program $program): View
    {
        $status = ApplicationStatus::tryFrom((string) $request->query('status'));

        return view('workspace.programs.show', [
            'program' => $program,
            'counts' => $this->applications->counts($program),
            'applications' => ProgramApplication::query()
                ->where('program_id', $program->id)
                ->where('status', '!=', ApplicationStatus::Draft)
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($request->query('search'), fn ($q, $term) => $q->where(fn ($q) => $q
                    ->where('reference', 'like', '%'.addcslashes($term, '%_\\').'%')
                    ->orWhereHas('applicant', fn ($u) => $u->search($term, ['name', 'email', 'institution']))))
                ->with(['applicant:id,name,email,affiliation,institution', 'reviewer:id,name'])
                ->latest('submitted_at')
                ->paginate(25)
                ->withQueryString(),
            'statuses' => ApplicationStatus::reviewable(),
            'activeStatus' => $status,
            'search' => $request->query('search'),
        ]);
    }

    public function edit(Program $program): View
    {
        return view('workspace.programs.edit', $this->formData($program));
    }

    public function update(ProgramRequest $request, Program $program): RedirectResponse
    {
        $wasPublished = $program->status === ProgramStatus::Published;
        $this->programs->update($program, $request->validated());

        return $this->afterSave($request, $program, 'Programme updated.', $wasPublished);
    }

    public function destroy(Program $program): RedirectResponse
    {
        if ($program->applications()->where('status', '!=', ApplicationStatus::Draft)->exists()) {
            return back()->withErrors(['program' => 'This programme has applications. Archive it instead of deleting it.']);
        }

        $this->programs->delete($program);

        return redirect()->route('workspace.programs.index')->with('status', "\"{$program->title}\" was deleted.");
    }

    public function announce(Request $request, Program $program): RedirectResponse
    {
        abort_unless($program->status === ProgramStatus::Published, 422, 'Only published programmes can be announced.');

        $broadcast = $this->programs->announce($program, $request->user());

        return back()->with('status', "Announcement sent to {$broadcast->recipients_count} member(s).");
    }

    public function export(Program $program): StreamedResponse
    {
        return $this->applications->exportCsv($program);
    }

    private function afterSave(ProgramRequest $request, Program $program, string $message, bool $wasPublished = false): RedirectResponse
    {
        if ($request->boolean('notify_members') && $program->status === ProgramStatus::Published && ! $wasPublished) {
            $broadcast = $this->programs->announce($program, $request->user());
            $message .= " Announcement sent to {$broadcast->recipients_count} member(s).";
        }

        return redirect()->route('workspace.programs.show', $program)->with('status', $message);
    }

    /** @return array<string, mixed> */
    private function formData(Program $program): array
    {
        return [
            'program' => $program,
            'types' => ProgramType::options(),
            'statuses' => ProgramStatus::options(),
            'focusAreas' => config('hub.focus_areas'),
            'fieldTypes' => FormSchema::TYPES,
            'withOptions' => FormSchema::WITH_OPTIONS,
            'hasApplications' => $program->exists && $program->applications()->where('status', '!=', ApplicationStatus::Draft)->exists(),
        ];
    }
}

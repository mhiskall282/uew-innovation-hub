<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramApplication;
use App\Services\ApplicationService;
use App\Support\FormSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** A member's own applications. Every action checks ownership via policy. */
class ApplicationController extends Controller
{
    public function __construct(private readonly ApplicationService $applications) {}

    public function index(Request $request): View
    {
        return view('portal.applications.index', [
            'applications' => ProgramApplication::query()
                ->where('user_id', $request->user()->id)
                ->with('program:id,title,slug,type,closes_at,status')
                ->latest('updated_at')
                ->paginate(15),
        ]);
    }

    public function show(Request $request, ProgramApplication $application): View
    {
        Gate::authorize('view', $application);

        return view('portal.applications.show', ['application' => $application->load('program')]);
    }

    /** Application form for an open programme (new or draft). */
    public function create(Request $request, Program $program): View|RedirectResponse
    {
        abort_unless($program->isVisible(), 404);

        $application = $this->applications->forMember($program, $request->user());

        if ($application->exists && ! $application->isEditable()) {
            return redirect()->route('portal.applications.show', $application)
                ->with('status', 'You have already applied to this programme.');
        }

        if (! $program->isAcceptingApplications()) {
            return redirect()->route('site.programs.show', $program)
                ->withErrors(['application' => 'This programme is not accepting applications right now.']);
        }

        return view('portal.applications.form', ['program' => $program, 'application' => $application]);
    }

    public function store(Request $request, Program $program): RedirectResponse
    {
        abort_unless($program->isVisible(), 404);

        $submitting = $request->input('intent') === 'submit';
        $existing = $this->applications->forMember($program, $request->user());
        $fields = $program->fields();

        $validated = $request->validate(
            FormSchema::rules($fields, $submitting, $existing->answers ?? []) + [
                'intent' => ['required', 'in:draft,submit'],
                'declaration' => $submitting ? ['accepted'] : ['nullable'],
            ],
            ['declaration.accepted' => 'Confirm that your answers are accurate before submitting.'],
            FormSchema::attributes($fields),
        );

        $application = $this->applications->save(
            $program,
            $request->user(),
            $validated['answers'] ?? [],
            array_filter($request->file('files', []) ?? []),
            $submitting,
        );

        return $submitting
            ? redirect()->route('portal.applications.show', $application)->with('status', 'Application submitted. We have e-mailed you a confirmation.')
            : redirect()->route('portal.apply', $program)->with('status', 'Draft saved. You can come back and finish it before the deadline.');
    }

    public function withdraw(Request $request, ProgramApplication $application): RedirectResponse
    {
        Gate::authorize('withdraw', $application);

        $this->applications->withdraw($application, $request->user());

        return redirect()->route('portal.applications.show', $application)->with('status', 'Your application was withdrawn.');
    }

    public function file(ProgramApplication $application, string $key): StreamedResponse
    {
        Gate::authorize('view', $application);

        return $this->applications->download($application, $key);
    }
}

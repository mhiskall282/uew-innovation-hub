<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AchievementType;
use App\Enums\ApplicationStatus;
use App\Enums\Permission;
use App\Mail\TemplatedMail;
use App\Models\Program;
use App\Models\ProgramApplication;
use App\Models\User;
use App\Notifications\HubNotification;
use App\Support\Demographics;
use App\Support\FormSchema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class ApplicationService
{
    public const FOLDER = 'applications';

    public function __construct(
        private readonly DocumentStorage $documents,
        private readonly AuditLogger $audit,
        private readonly StaffNotifier $staff,
        private readonly NotificationCenter $notifications,
        private readonly EngagementService $engagement,
    ) {}

    /** The member's application for a programme, or a new unsaved draft. */
    public function forMember(Program $program, User $user): ProgramApplication
    {
        return ProgramApplication::query()
            ->where('program_id', $program->id)
            ->where('user_id', $user->id)
            ->first() ?? tap(new ProgramApplication, function (ProgramApplication $a) use ($program, $user) {
                $a->program_id = $program->id;
                $a->user_id = $user->id;
                $a->answers = [];
            });
    }

    /**
     * Save answers as a draft, or submit them.
     *
     * @param  array<string, mixed>  $answers  validated answers keyed by field key
     * @param  array<string, UploadedFile>  $files  validated uploads keyed by field key
     *
     * @throws ValidationException when the programme stopped accepting applications
     */
    public function save(Program $program, User $user, array $answers, array $files, bool $submit): ProgramApplication
    {
        if (! $program->isAcceptingApplications()) {
            throw ValidationException::withMessages(['application' => 'This programme is no longer accepting applications.']);
        }

        $application = DB::transaction(function () use ($program, $user, $answers, $files, $submit) {
            $application = $this->forMember($program, $user);

            if ($application->exists && ! $application->isEditable()) {
                throw ValidationException::withMessages(['application' => 'This application has already been submitted and can no longer be changed.']);
            }

            $stored = $application->answers ?? [];
            $known = collect($program->fields())->keyBy('key');

            foreach ($known as $key => $field) {
                if ($field['type'] === 'file') {
                    if (isset($files[$key])) {
                        $stored[$key] = $this->documents->replace($stored[$key] ?? null, $files[$key], self::FOLDER, 'application');
                    }

                    continue;
                }

                $stored[$key] = $answers[$key] ?? null;
            }

            // Drop answers for fields that no longer exist on the form.
            $application->answers = array_intersect_key($stored, $known->all());

            if ($submit) {
                $application->status = ApplicationStatus::Submitted;
                $application->submitted_at = now();
                $application->applicant_snapshot = Demographics::snapshot($user);
            }

            $isNew = ! $application->exists;
            $application->save();

            if ($submit) {
                $this->audit->record('application.submitted', "{$user->name} submitted an application to \"{$program->title}\"", $application, actor: $user);
            } elseif ($isNew) {
                $this->audit->record('application.started', "{$user->name} started an application to \"{$program->title}\"", $application, actor: $user);
            }

            return $application;
        });

        if ($submit) {
            $this->engagement->addXp($user, EngagementService::XP_APPLICATION);
            $this->notifySubmitted($application, $program, $user);
        }

        return $application;
    }

    public function withdraw(ProgramApplication $application, User $user): void
    {
        if (! $application->status->canWithdraw()) {
            throw ValidationException::withMessages(['application' => 'This application can no longer be withdrawn.']);
        }

        $application->status = ApplicationStatus::Withdrawn;
        $application->save();

        $this->audit->record('application.withdrawn', "{$user->name} withdrew their application {$application->reference}", $application, actor: $user);
    }

    /**
     * @param  array{status: string, internal_notes?: ?string, decision_message?: ?string, score?: ?int, notify?: bool}  $data
     */
    public function review(ProgramApplication $application, array $data, User $reviewer): ProgramApplication
    {
        $previous = $application->status;
        $status = ApplicationStatus::from($data['status']);

        $application->status = $status;
        $application->internal_notes = $data['internal_notes'] ?? $application->internal_notes;
        $application->decision_message = $data['decision_message'] ?? null;
        $application->score = $data['score'] ?? $application->score;
        $application->reviewed_by = $reviewer->id;
        if ($status->isFinal()) {
            $application->decided_at = now();
        }

        $changes = $this->audit->diff($application);
        unset($changes['decided_at']);
        $application->save();

        $this->audit->record('application.reviewed', "Set application {$application->reference} to \"{$status->label()}\"", $application, $changes);

        if ($previous !== $status) {
            $application->loadMissing('applicant', 'program');

            // In-app notice always; e-mail only when the reviewer chose to send it.
            $this->notifications->toUser($application->applicant, new HubNotification(
                title: "{$application->program->title}: {$status->label()}",
                body: $status->applicantMessage(),
                url: route('portal.applications.show', $application),
                icon: $status === ApplicationStatus::Approved ? 'check-circle' : 'inbox',
                kind: 'application',
            ));

            if ($status === ApplicationStatus::Approved && $application->applicant) {
                $this->engagement->award($application->applicant, AchievementType::Participant, $application->program, by: $reviewer);
            }

            if ($data['notify'] ?? false) {
                $this->notifyStatus($application);
            }
        }

        return $application;
    }

    /**
     * Apply one decision to many submitted applications in a programme.
     *
     * @param  list<int>  $ids
     */
    public function bulkReview(Program $program, array $ids, ApplicationStatus $status, bool $notify, User $reviewer): int
    {
        $count = 0;

        ProgramApplication::query()
            ->where('program_id', $program->id)
            ->whereIn('id', $ids)
            ->whereNotIn('status', [ApplicationStatus::Draft, ApplicationStatus::Withdrawn])
            ->with('applicant:id,name,email', 'program:id,title,slug')
            ->get()
            ->each(function (ProgramApplication $application) use ($status, $notify, $reviewer, &$count) {
                $this->review($application, ['status' => $status->value, 'notify' => $notify, 'decision_message' => $application->decision_message], $reviewer);
                $count++;
            });

        return $count;
    }

    public function download(ProgramApplication $application, string $key): StreamedResponse
    {
        return $this->documents->download($application->answer($key), self::FOLDER, $application->reference.'-'.$key);
    }

    /** Streams every submitted application for a programme as CSV. */
    public function exportCsv(Program $program): StreamedResponse
    {
        $fields = collect($program->fields())->reject(fn ($f) => $f['type'] === 'file')->values();
        $filename = 'applications-'.$program->slug.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($program, $fields) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_merge(
                ['Reference', 'Status', 'Applicant', 'Email', 'Phone', 'Affiliation', 'Institution', 'Submitted', 'Score'],
                $fields->pluck('label')->all(),
            ));

            ProgramApplication::query()
                ->where('program_id', $program->id)
                ->where('status', '!=', ApplicationStatus::Draft)
                ->with('applicant:id,name,email,phone,affiliation,institution')
                ->orderBy('id')
                ->lazy(500)
                ->each(function (ProgramApplication $a) use ($out, $fields) {
                    fputcsv($out, array_map([self::class, 'cell'], array_merge([
                        $a->reference, $a->status->label(), $a->applicant?->name, $a->applicant?->email, $a->applicant?->phone,
                        $a->applicant?->affiliation?->label(), $a->applicant?->institution,
                        $a->submitted_at?->format('Y-m-d H:i'), $a->score,
                    ], $fields->map(fn ($f) => FormSchema::display($f, $a->answer($f['key'])))->all())));
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }

    /** @return Collection<string, int> status => count for a programme */
    public function counts(Program $program): Collection
    {
        return ProgramApplication::query()
            ->where('program_id', $program->id)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($n) => (int) $n);
    }

    public static function cell(mixed $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'{$value}" : $value;
    }

    private function notifySubmitted(ProgramApplication $application, Program $program, User $user): void
    {
        $this->notifications->toUser($user, new HubNotification(
            title: 'Application submitted',
            body: "We received your application to {$program->title} ({$application->reference}). +".EngagementService::XP_APPLICATION.' XP.',
            url: route('portal.applications.show', $application),
            icon: 'check-circle',
            kind: 'application',
        ));
        $this->notifications->toStaff(Permission::ApplicationsReview, new HubNotification(
            title: 'New application',
            body: "{$user->name} applied to {$program->title}.",
            url: route('workspace.applications.show', $application),
            icon: 'inbox',
            kind: 'application',
        ), except: $user);

        $this->queue(fn () => Mail::to($user)->queue(new TemplatedMail('application_submitted', [
            'name' => $user->name, 'first_name' => $user->firstName(),
            'program' => $program->title, 'reference' => $application->reference,
        ], route('portal.applications.show', $application))));

        $this->staff->notify(Permission::ApplicationsReview, 'notify_new_grants', fn (User $staff) => new TemplatedMail('staff_new_application', [
            'name' => $staff->name, 'first_name' => $staff->firstName(),
            'program' => $program->title, 'applicant' => $user->name, 'reference' => $application->reference,
        ], route('workspace.applications.show', $application)));
    }

    private function notifyStatus(ProgramApplication $application): void
    {
        $application->loadMissing('applicant', 'program');
        $user = $application->applicant;

        if (! $user) {
            return;
        }

        $this->queue(fn () => Mail::to($user)->queue(new TemplatedMail('application_status', [
            'name' => $user->name, 'first_name' => $user->firstName(),
            'program' => $application->program->title, 'reference' => $application->reference,
            'status' => $application->status->label(),
            'status_message' => $application->status->applicantMessage(),
            'decision_message' => (string) $application->decision_message,
        ], route('portal.applications.show', $application))));
    }

    private function queue(callable $send): void
    {
        try {
            $send();
        } catch (Throwable $e) {
            Log::warning('Application e-mail could not be queued.', ['error' => $e->getMessage()]);
        }
    }
}

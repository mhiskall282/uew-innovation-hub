<?php

declare(strict_types=1);

namespace Tests\Feature\Workspace;

use App\Enums\ApplicationStatus;
use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Program;
use App\Models\ProgramApplication;
use App\Models\User;
use App\Support\DateRange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/** Reports page periods and the PDF export. */
class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_date_range_presets_and_custom_ranges(): void
    {
        $this->travelTo('2026-11-24 10:00:00');

        $range = DateRange::fromRequest(Request::create('/', 'GET', ['range' => '30d']));
        $this->assertSame('2026-10-26', $range->from->toDateString());
        $this->assertSame('2026-11-24', $range->to->toDateString());

        $range = DateRange::fromRequest(Request::create('/', 'GET', ['range' => 'custom', 'from' => '2026-03-01', 'to' => '2026-03-31']));
        $this->assertSame('custom', $range->preset);
        $this->assertSame(['range' => 'custom', 'from' => '2026-03-01', 'to' => '2026-03-31'], $range->query());

        // Reversed, impossible or malformed dates fall back to the default.
        foreach ([['2026-05-01', '2026-04-01'], ['2026-02-31', '2026-03-01'], ['yesterday', '2026-03-01'], [null, null]] as [$from, $to]) {
            $this->assertSame('12m', DateRange::fromRequest(Request::create('/', 'GET', ['range' => 'custom', 'from' => $from, 'to' => $to]))->preset);
        }
        $this->assertSame('12m', DateRange::fromRequest(Request::create('/', 'GET', ['range' => 'evil']))->preset);

        // Future dates are clamped to today.
        $range = DateRange::fromRequest(Request::create('/', 'GET', ['range' => 'custom', 'from' => '2026-01-01', 'to' => '2030-01-01']));
        $this->assertSame('2026-11-24', $range->to->toDateString());
    }

    public function test_reports_count_only_applications_in_the_period(): void
    {
        $program = Program::factory()->published()->create();
        ProgramApplication::factory()->between($program, User::factory()->member()->create())->status(ApplicationStatus::Submitted)->create();
        $old = ProgramApplication::factory()->between($program, User::factory()->member()->create())->status(ApplicationStatus::Submitted)->create();
        $old->forceFill(['submitted_at' => now()->subMonths(6)])->save();

        $this->actingAs($this->staff(Role::Reviewer));

        $this->get(route('workspace.reports.index', ['range' => '30d']))->assertOk()
            ->assertViewHas('totals', fn ($t) => $t['applications'] === 1);
        $this->get(route('workspace.reports.index', ['range' => 'all']))->assertOk()
            ->assertViewHas('totals', fn ($t) => $t['applications'] === 2);
        $this->get(route('workspace.reports.index', ['range' => 'custom', 'from' => '2015-01-01', 'to' => 'nonsense']))->assertOk();
    }

    public function test_pdf_export_downloads_and_is_audited(): void
    {
        ProgramApplication::factory()->status(ApplicationStatus::Approved)->create();
        $reviewer = $this->staff(Role::Reviewer);

        $response = $this->actingAs($reviewer)->get(route('workspace.reports.pdf', [
            'range' => 'custom', 'from' => now()->subMonth()->toDateString(), 'to' => now()->toDateString(),
            'sections' => ['programmes', 'incubation', 'not-a-section'],
        ]));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('attachment; filename=uew-innovation-hub-report-', (string) $response->headers->get('content-disposition'));

        $log = ActivityLog::where('action', 'report.exported')->sole();
        $this->assertSame($reviewer->id, $log->user_id);
        $this->assertSame(['programmes', 'incubation'], $log->properties['sections']);
    }

    public function test_pdf_export_requires_reports_permission(): void
    {
        foreach ([Role::Viewer, Role::Intern] as $role) {
            $this->actingAs($this->staff($role))->get(route('workspace.reports.pdf'))->assertForbidden();
        }
        $this->actingAs(User::factory()->member()->create())->get(route('workspace.reports.pdf'))->assertForbidden();

        auth()->logout();
        $this->get(route('workspace.reports.pdf'))->assertRedirect(route('login'));
    }
}

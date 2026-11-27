<?php

declare(strict_types=1);

namespace Tests\Feature\Workspace;

use App\Enums\ProgramStatus;
use App\Enums\Role;
use App\Jobs\SendBroadcast;
use App\Mail\TemplatedMail;
use App\Models\Broadcast;
use App\Models\ContactMessage;
use App\Models\Page;
use App\Models\Program;
use App\Models\Setting;
use App\Models\User;
use App\Services\BroadcastService;
use App\Services\MailSettings;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** Programmes, settings and SMTP, broadcasts, CMS pages, contact form, staff account and admin bootstrap. */
class AdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_officer_creates_publishes_and_deletes_a_programme_with_a_form(): void
    {
        $officer = $this->signInAs(Role::Officer);

        $this->post(route('workspace.programs.store'), [
            'title' => 'Climate Hackathon 2027', 'type' => 'competition', 'focus_area' => 'Agriculture',
            'summary' => 'A weekend hackathon for climate-smart farming ideas.', 'status' => 'published',
            'fields' => [
                ['label' => 'Team name', 'type' => 'short_text', 'required' => '1', 'prefill' => ''],
                ['label' => 'Your region', 'type' => 'short_text', 'prefill' => 'region'],
                ['label' => 'Track', 'type' => 'select', 'options' => "Soil\nWater"],
            ],
        ])->assertRedirect();

        $program = Program::where('title', 'Climate Hackathon 2027')->sole();
        $this->assertSame(ProgramStatus::Published, $program->status);
        $this->assertSame($officer->id, $program->created_by);
        $this->assertSame(['team_name', 'your_region', 'track'], array_column($program->fields(), 'key'));
        $this->assertSame('region', $program->fields()[1]['prefill']);
        $this->assertSame(['Soil', 'Water'], $program->fields()[2]['options']);
        $this->get(route('site.programs.show', $program))->assertOk()->assertSee('Climate Hackathon 2027');

        $this->delete(route('workspace.programs.destroy', $program))->assertRedirect();
        $this->assertSoftDeleted($program);
        $this->get(route('site.programs.show', $program->slug))->assertNotFound();
    }

    public function test_programme_validation_rejects_bad_input(): void
    {
        $this->signInAs(Role::Officer);

        $this->post(route('workspace.programs.store'), [
            'title' => '', 'type' => 'party', 'summary' => '', 'status' => 'live',
            'fields' => [['label' => 'Q', 'type' => 'script', 'prefill' => 'password']],
        ])->assertSessionHasErrors(['title', 'type', 'summary', 'status', 'fields.0.type', 'fields.0.prefill']);
        $this->assertSame(0, Program::count());
    }

    public function test_reviewers_cannot_manage_programmes(): void
    {
        $this->signInAs(Role::Reviewer);
        $this->get(route('workspace.programs.create'))->assertForbidden();
        $this->post(route('workspace.programs.store'), ['title' => 'x'])->assertForbidden();
    }

    public function test_smtp_password_is_stored_encrypted_and_kept_when_left_blank(): void
    {
        $this->signInAs(Role::SuperAdmin);
        $smtp = ['mail_mailer' => 'smtp', 'mail_host' => 'smtp.example.org', 'mail_port' => 587, 'mail_encryption' => 'tls',
            'mail_username' => 'hub', 'mail_from_address' => 'hub@example.org', 'mail_from_name' => 'Hub'];

        $this->put(route('workspace.settings.mail'), $smtp + ['mail_password' => 'S3cret-smtp-pass'])->assertRedirect();

        $stored = (string) DB::table('settings')->where('key', 'mail_password')->value('value');
        $this->assertNotSame('', $stored);
        $this->assertStringNotContainsString('S3cret-smtp-pass', $stored);

        // Blank password keeps the saved one.
        $this->put(route('workspace.settings.mail'), $smtp + ['mail_password' => ''])->assertRedirect();
        app()->forgetInstance(SettingsService::class);
        app(MailSettings::class)->apply();
        $this->assertSame('S3cret-smtp-pass', config('mail.mailers.smtp.password'));
        $this->assertSame('smtp.example.org', config('mail.mailers.smtp.host'));

        // The page never echoes the password back.
        $this->get(route('workspace.settings.edit', ['tab' => 'mail']))->assertOk()->assertDontSee('S3cret-smtp-pass');
    }

    public function test_only_the_hub_director_changes_site_settings(): void
    {
        $this->signInAs(Role::Officer);
        $this->get(route('workspace.settings.edit'))->assertForbidden();
        $this->put(route('workspace.settings.mail'), ['mail_mailer' => 'log'])->assertForbidden();
        $this->assertSame(0, Setting::count());
    }

    public function test_site_settings_update_the_public_site(): void
    {
        $this->signInAs(Role::SuperAdmin);

        $this->put(route('workspace.settings.update'), [
            'contact_email' => 'hub@uew.edu.gh', 'contact_phone' => '+233 30 000 1111', 'address' => 'North Campus, Winneba',
            'hero_eyebrow' => 'UEW', 'hero_title' => 'Ideas into impact', 'hero_subtitle' => 'Grants and incubation for UEW innovators.',
            'cta_title' => 'Apply', 'cta_text' => 'Open calls are listed on the programmes page.', 'footer_about' => 'The Innovation Hub of UEW.',
            'announcement_enabled' => '1', 'announcement_text' => 'Applications close Friday', 'registration_open' => '1',
        ])->assertRedirect();

        auth()->logout();
        app()->forgetInstance(SettingsService::class);
        $this->get('/')->assertOk()->assertSee('Ideas into impact')->assertSee('Applications close Friday')->assertSee('+233 30 000 1111');
    }

    public function test_broadcast_reaches_only_opted_in_matching_members_and_unsubscribe_link_is_signed(): void
    {
        $this->signInAs(Role::Officer);
        $match = User::factory()->member()->create(['interests' => ['STEM'], 'marketing_opt_in' => true]);
        User::factory()->member()->create(['interests' => ['Agriculture'], 'marketing_opt_in' => true]);
        User::factory()->member()->create(['interests' => ['STEM'], 'marketing_opt_in' => false]);

        $this->post(route('workspace.broadcasts.store'), [
            'subject' => 'Robotics bootcamp', 'body' => 'Hello {{ first_name }}, join us.', 'interests' => ['STEM'], 'intent' => 'send',
        ])->assertRedirect(route('workspace.broadcasts.index'));

        $broadcast = Broadcast::sole();
        $this->assertSame(1, $broadcast->recipients_count);
        (new SendBroadcast($broadcast->id))->handle(app(BroadcastService::class));
        Mail::assertQueued(TemplatedMail::class, fn ($mail) => $mail->hasTo($match->email));
        Mail::assertQueued(TemplatedMail::class, 1);

        auth()->logout();
        $this->get(route('site.unsubscribe', $match))->assertForbidden(); // unsigned
        // Opening the link only asks for confirmation (mail scanners pre-open links).
        $this->get(URL::signedRoute('site.unsubscribe', $match))->assertOk()->assertSee('Stop announcement e-mails?');
        $this->assertTrue($match->fresh()->marketing_opt_in);
        $this->post(URL::signedRoute('site.unsubscribe.store', $match))->assertOk()->assertSee('You have been unsubscribed');
        $this->assertFalse($match->fresh()->marketing_opt_in);
    }

    public function test_cms_page_markdown_cannot_inject_html_or_scripts(): void
    {
        $this->signInAs(Role::Officer);

        $this->post(route('workspace.pages.store'), [
            'title' => 'Mentorship', 'slug' => 'mentorship', 'is_published' => '1',
            'body' => "## Mentors\n<script>alert(1)</script>\n[click](javascript:alert(2)) <img src=x onerror=alert(3)>",
        ])->assertRedirect();

        auth()->logout();
        $html = $this->get('/mentorship')->assertOk()->assertSee('Mentors')->getContent();
        $this->assertStringNotContainsString('<script>alert(1)', $html);
        $this->assertStringNotContainsString('javascript:alert(2)', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
    }

    public function test_cms_pages_cannot_take_reserved_or_existing_slugs(): void
    {
        $this->signInAs(Role::Officer);

        foreach (['community', 'workspace', 'sign-in'] as $slug) {
            $this->post(route('workspace.pages.store'), ['title' => 'X', 'slug' => $slug, 'body' => 'x'])->assertSessionHasErrors('slug');
        }
        $this->assertSame(0, Page::count());
    }

    public function test_contact_form_stores_messages_and_rejects_bots(): void
    {
        $this->post(route('site.contact.store'), [
            'name' => 'Ama', 'email' => 'ama@example.com', 'subject' => 'Partnership', 'message' => 'We would like to sponsor a challenge.',
        ])->assertRedirect(route('site.contact'));
        $this->assertSame(1, ContactMessage::count());

        $this->post(route('site.contact.store'), [
            'name' => 'Bot', 'email' => 'bot@example.com', 'subject' => 'Buy', 'message' => 'Cheap followers here!!', 'company_website' => 'http://spam.example',
        ])->assertSessionHasErrors('company_website');
        $this->assertSame(1, ContactMessage::count());
    }

    public function test_contact_form_is_rate_limited(): void
    {
        foreach (range(1, 3) as $i) {
            $this->post(route('site.contact.store'), ['name' => 'A', 'email' => "a{$i}@example.com", 'subject' => 'Hi', 'message' => 'A message long enough.']);
        }
        $this->post(route('site.contact.store'), ['name' => 'A', 'email' => 'a4@example.com', 'subject' => 'Hi', 'message' => 'A message long enough.'])
            ->assertStatus(429);
    }

    public function test_staff_change_password_needs_current_password_and_revokes_nothing_silently(): void
    {
        $user = $this->signInAs(Role::Viewer, ['password' => 'Old-Pass-2026x']);

        $this->put(route('workspace.profile.password'), ['current_password' => 'wrong', 'password' => 'New-Pass-2026x', 'password_confirmation' => 'New-Pass-2026x'])
            ->assertSessionHasErrors('current_password');
        $this->put(route('workspace.profile.password'), ['current_password' => 'Old-Pass-2026x', 'password' => 'Old-Pass-2026x', 'password_confirmation' => 'Old-Pass-2026x'])
            ->assertSessionHasErrors('password');
        $this->put(route('workspace.profile.password'), ['current_password' => 'Old-Pass-2026x', 'password' => 'New-Pass-2026x', 'password_confirmation' => 'New-Pass-2026x'])
            ->assertRedirect();

        $this->assertTrue(Hash::check('New-Pass-2026x', $user->fresh()->password));
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'action' => 'auth.password_changed']);
    }

    public function test_create_admin_command_is_idempotent(): void
    {
        $this->artisan('hub:create-admin', ['--email' => 'director@uew.edu.gh', '--name' => 'Director', '--password' => 'Director-Pass-2026'])->assertSuccessful();
        $admin = User::where('email', 'director@uew.edu.gh')->sole();
        $this->assertSame(Role::SuperAdmin, $admin->role);
        $this->assertNotNull($admin->email_verified_at);

        $this->artisan('hub:create-admin', ['--email' => 'second@uew.edu.gh', '--password' => 'Director-Pass-2026'])->assertSuccessful();
        $this->assertSame(1, User::where('role', Role::SuperAdmin)->count());
    }
}

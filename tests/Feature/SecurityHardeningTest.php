<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Post;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Audit findings F-01, F-03, F-04, F-07: regression tests. */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_passwords_are_refused_when_blocking_is_on(): void
    {
        config(['hub.block_default_passwords' => true]);
        $user = $this->staff(Role::SuperAdmin, ['email' => 'boss@example.com', 'password' => 'password']);

        $this->post('/sign-in', ['email' => 'boss@example.com', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'This password is not allowed on this site. Use "Forgot password?" to set a new one, or ask the Hub administrator.']);
        $this->assertGuest();

        // Same message for an account that does not exist: no account oracle.
        $this->post('/sign-in', ['email' => 'nobody@example.com', 'password' => 'Password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertSame(2, ActivityLog::where('action', 'auth.default_password_refused')->count());

        // A strong password still works.
        $user->forceFill(['password' => 'Strong-Pass-2026'])->save();
        $this->post('/sign-in', ['email' => 'boss@example.com', 'password' => 'Strong-Pass-2026'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_default_passwords_still_work_when_blocking_is_off(): void
    {
        config(['hub.block_default_passwords' => false]);
        $user = $this->staff(Role::Viewer, ['email' => 'dev@example.com', 'password' => 'password']);

        $this->post('/sign-in', ['email' => 'dev@example.com', 'password' => 'password'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_one_account_is_locked_after_many_failures_from_different_ips(): void
    {
        $user = $this->staff(Role::Officer, ['email' => 'target@example.com', 'password' => 'Right-Pass-2026']);

        foreach (range(1, 20) as $i) {
            // Four attempts per IP stays under the per-IP limit of five.
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.'.intdiv($i, 4).'.1'])
                ->post('/sign-in', ['email' => 'target@example.com', 'password' => "wrong-{$i}"]);
        }

        // Even the right password from a fresh IP is now refused.
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.99'])
            ->post('/sign-in', ['email' => 'target@example.com', 'password' => 'Right-Pass-2026'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertNotNull($user);
    }

    public function test_robots_and_sitemap_follow_the_indexing_setting(): void
    {
        config(['hub.search_indexing' => false]);
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /', false)->assertDontSee('Sitemap:');
        $this->get('/')->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        config(['hub.search_indexing' => true]);
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /workspace')->assertSee('Sitemap: '.route('site.sitemap'));
        $this->get('/')->assertHeaderMissing('X-Robots-Tag');
        // Private areas stay noindex regardless.
        $this->get('/sign-in')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_sitemap_lists_only_public_content(): void
    {
        $open = Program::factory()->published()->create(['title' => 'Open Call']);
        $draft = Program::factory()->draft()->create(['title' => 'Hidden Draft']);
        $post = Post::factory()->create();

        $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $response->assertSee(route('site.programs.show', $open), false)
            ->assertDontSee(route('site.programs.show', $draft), false)
            ->assertDontSee('/workspace', false);
        $this->assertStringStartsWith('<?xml', $response->getContent());
        $this->assertNotNull($post);
    }

    public function test_placeholder_phone_number_is_not_shown(): void
    {
        $this->get('/contact')->assertOk()->assertDontSee('000 0000');
    }

    public function test_reviewers_cannot_be_given_extra_rights_by_posting_role_fields(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)->put(route('portal.profile.update'), [
            'name' => 'X', 'phone' => '+233 24 000 1234', 'gender' => 'male', 'date_of_birth' => '2000-01-01', 'region' => 'Volta',
            'affiliation' => 'student', 'education_level' => 'undergraduate', 'interests' => ['STEM'],
            'role' => 'super_admin', 'is_active' => 1, 'xp' => 99999,
        ]);

        $member->refresh();
        $this->assertSame(Role::Member, $member->role);
        $this->assertNotSame(99999, $member->xp);
    }
}

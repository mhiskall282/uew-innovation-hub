<?php

declare(strict_types=1);

namespace Tests\Feature\Workspace;

use App\Enums\Permission;
use App\Enums\Role;
use App\Mail\TemplatedMail;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route as Router;
use Tests\TestCase;

/** Least privilege across the workspace, and the administrator safeguards. */
class PrivilegesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    /**
     * Every state-changing workspace route that is gated by a `can:` middleware
     * must refuse each role that lacks that permission, and send guests to sign in.
     */
    public function test_every_state_changing_workspace_route_enforces_its_permission(): void
    {
        // Rate limits would answer 429 before authorisation runs; this test is about permissions.
        $this->withoutMiddleware(ThrottleRequests::class);

        $actors = collect(Role::cases())->mapWithKeys(fn (Role $r) => [$r->value => User::factory()->role($r)->create()]);
        $target = User::factory()->member()->create();
        $checked = 0;

        /** @var Route $route */
        foreach (Router::getRoutes() as $route) {
            $method = collect($route->methods())->first(fn ($m) => in_array($m, ['POST', 'PUT', 'PATCH', 'DELETE'], true));
            // Skip plain redirects (old URLs such as /workspace/sign-in).
            if (! $method || ! str_starts_with($route->uri(), 'workspace') || str_contains((string) $route->getActionName(), 'RedirectController')) {
                continue;
            }

            $abilities = collect($route->gatherMiddleware())
                ->filter(fn ($m) => is_string($m) && str_starts_with($m, 'can:'))
                ->map(fn ($m) => explode(',', substr($m, 4))[0])
                ->map(fn ($a) => Permission::tryFrom($a))
                ->filter();

            // Any model id works: authorisation runs before the model is used.
            $uri = '/'.preg_replace('/\{[^}]+\}/', (string) $target->id, $route->uri());

            $this->app['auth']->forgetGuards();
            $guest = $this->call($method, $uri);
            $this->assertTrue($guest->isRedirect(route('login')) || $guest->status() === 404, "Guest reached {$method} {$uri} ({$guest->status()})");

            foreach ($actors as $role => $user) {
                $lacks = $abilities->first(fn (Permission $p) => ! Role::from($role)->allows($p));
                if (! $lacks) {
                    continue;
                }
                $this->app['auth']->forgetGuards();
                $status = $this->actingAs($user)->call($method, $uri)->status();
                $this->assertContains($status, [403, 404], "{$role} without {$lacks->value} got {$status} on {$method} {$uri}");
                $checked++;
            }
        }

        // 144 role/route pairs at the time of writing; guards against the loop silently skipping everything.
        $this->assertGreaterThanOrEqual(120, $checked, 'Expected the route matrix to cover many role/route pairs.');
    }

    public function test_nobody_can_change_their_own_role(): void
    {
        $director = $this->signInAs(Role::SuperAdmin);
        $this->staff(Role::SuperAdmin); // another Director exists, so this isn't the last-admin guard

        $this->put(route('workspace.users.update', $director), [
            'name' => $director->name, 'email' => $director->email, 'role' => 'viewer',
        ])->assertSessionHasErrors(['role' => 'You cannot change your own role. Ask another Hub Director.']);
        $this->assertSame(Role::SuperAdmin, $director->fresh()->role);

        $this->get(route('workspace.users.edit', $director))->assertOk()->assertSee("You can't change your own role", false);
    }

    public function test_only_users_manage_can_change_roles_and_nobody_escalates_through_their_profile(): void
    {
        $officer = $this->signInAs(Role::Officer);
        $viewer = $this->staff(Role::Viewer);

        $this->put(route('workspace.users.update', $viewer), ['name' => 'x', 'email' => $viewer->email, 'role' => 'super_admin'])->assertForbidden();
        $this->put(route('workspace.profile.update'), ['name' => 'Me', 'email' => $officer->email, 'role' => 'super_admin', 'is_active' => 0]);

        $this->assertSame(Role::Viewer, $viewer->fresh()->role);
        $this->assertSame(Role::Officer, $officer->fresh()->role);
        $this->assertTrue($officer->fresh()->is_active);
    }

    public function test_staff_form_cannot_turn_anyone_into_a_member(): void
    {
        $this->signInAs(Role::SuperAdmin);
        $viewer = $this->staff(Role::Viewer);

        $this->put(route('workspace.users.update', $viewer), ['name' => 'x', 'email' => $viewer->email, 'role' => 'member'])->assertSessionHasErrors('role');
        $this->post(route('workspace.users.store'), ['name' => 'x', 'email' => 'm@example.com', 'role' => 'member', 'password' => 'Strong-Pass-2026x', 'password_confirmation' => 'Strong-Pass-2026x'])
            ->assertSessionHasErrors('role');
        $this->assertSame(Role::Viewer, $viewer->fresh()->role);
    }

    public function test_role_changes_are_audited_with_old_and_new_values(): void
    {
        $this->signInAs(Role::SuperAdmin);
        $user = $this->staff(Role::Viewer);

        $this->put(route('workspace.users.update', $user), ['name' => $user->name, 'email' => $user->email, 'role' => 'reviewer'])->assertRedirect();

        $log = ActivityLog::where('action', 'user.updated')->where('subject_id', $user->id)->sole();
        $this->assertSame(['from' => 'viewer', 'to' => 'reviewer'], $log->properties['role']);
    }

    public function test_director_sends_password_reset_and_verification_emails_without_knowing_passwords(): void
    {
        $this->signInAs(Role::SuperAdmin);
        $staff = $this->staff(Role::Officer);
        $member = User::factory()->member()->create(['email_verified_at' => null]);

        $this->post(route('workspace.users.password-reset', $staff))->assertRedirect()->assertSessionHas('status');
        Mail::assertSent(TemplatedMail::class, fn ($m) => $m->hasTo($staff->email) && $m->templateKey === 'password_reset_code' && str_contains((string) $m->actionUrl, '/password/reset/'));

        $this->post(route('workspace.members.verification', $member))->assertRedirect();
        Mail::assertSent(TemplatedMail::class, fn ($m) => $m->hasTo($member->email) && $m->templateKey === 'verification_code');
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.password-reset_sent', 'subject_id' => $staff->id]);

        // Throttled: a second request within a minute is refused politely.
        $this->post(route('workspace.users.password-reset', $staff))->assertSessionHasErrors('user');
    }

    public function test_officers_help_members_but_not_staff(): void
    {
        $this->signInAs(Role::Officer);
        $member = User::factory()->member()->create();
        $staff = $this->staff(Role::Viewer);

        $this->post(route('workspace.members.password-reset', $member))->assertRedirect();
        $this->post(route('workspace.users.password-reset', $staff))->assertForbidden();
        $this->post(route('workspace.members.password-reset', $staff))->assertForbidden();
    }

    public function test_roles_page_is_director_only_and_shows_the_matrix(): void
    {
        $this->signInAs(Role::SuperAdmin);
        $this->get(route('workspace.roles.index'))->assertOk()
            ->assertSee('Permission matrix')->assertSee(Permission::UsersManage->label())->assertSee('Programme Officer');

        foreach ([Role::Officer, Role::Reviewer, Role::Intern, Role::Viewer] as $role) {
            $this->signInAs($role);
            $this->get(route('workspace.roles.index'))->assertForbidden();
        }
    }

    public function test_sidebar_only_lists_what_the_role_can_open(): void
    {
        $this->signInAs(Role::Intern);
        $this->get(route('workspace.dashboard'))->assertOk()
            ->assertSee('Gallery')->assertSee('News &amp; posts', false)
            ->assertDontSee('Staff accounts')->assertDontSee('Roles &amp; permissions', false)->assertDontSee('Site settings')->assertDontSee('Members');
    }

    public function test_deactivated_staff_are_signed_out_on_their_next_request(): void
    {
        $viewer = $this->staff(Role::Viewer);
        $this->actingAs($viewer)->get(route('workspace.dashboard'))->assertOk();

        $viewer->forceFill(['is_active' => false])->save();
        $this->actingAs($viewer->fresh())->get(route('workspace.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}

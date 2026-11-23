<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sign_in_page_does_not_leak_credentials_or_demo_accounts(): void
    {
        $this->get('/sign-in')
            ->assertOk()
            ->assertSee('Sign in')
            ->assertDontSee('Pass:')
            ->assertDontSee('admin@uew.edu.gh')
            ->assertDontSee('value="password"', false)
            ->assertDontSee('Switch Role');
    }

    public function test_common_sign_in_urls_redirect(): void
    {
        $this->get('/login')->assertStatus(301)->assertRedirect('/sign-in');
        $this->get('/workspace/sign-in')->assertStatus(301)->assertRedirect('/sign-in');
        $this->get('/register')->assertStatus(301)->assertRedirect('/sign-up');
    }

    public function test_staff_sign_in_lands_in_the_workspace_and_regenerates_the_session(): void
    {
        $user = $this->staff(Role::Officer, ['email' => 'officer@uew.edu.gh']);
        $this->get('/sign-in');
        $before = session()->getId();

        $this->post('/sign-in', ['email' => 'OFFICER@uew.edu.gh', 'password' => 'password'])
            ->assertRedirect(route('workspace.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($before, session()->getId());
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.login', 'user_id' => $user->id]);
    }

    public function test_members_land_in_the_portal_never_the_workspace(): void
    {
        User::factory()->member()->create(['email' => 'abena@example.com']);

        $this->get('/workspace/projects'); // sets an intended URL in the workspace
        $this->post('/sign-in', ['email' => 'abena@example.com', 'password' => 'password'])
            ->assertRedirect(route('portal.dashboard'));
    }

    public function test_wrong_password_and_unknown_email_get_the_same_message(): void
    {
        $this->staff(Role::Officer, ['email' => 'real@uew.edu.gh']);

        $this->post('/sign-in', ['email' => 'real@uew.edu.gh', 'password' => 'nope'])->assertSessionHasErrors('email');
        $first = session('errors')->first('email');
        $this->post('/sign-in', ['email' => 'ghost@uew.edu.gh', 'password' => 'nope'])->assertSessionHasErrors('email');

        $this->assertSame($first, session('errors')->first('email'));
        $this->assertSame('These details do not match an active account.', $first);
        $this->assertGuest();
        $this->assertSame(2, ActivityLog::where('action', 'auth.login_failed')->count());
    }

    public function test_deactivated_accounts_cannot_sign_in(): void
    {
        $this->staff(Role::Officer, ['email' => 'gone@uew.edu.gh', 'is_active' => false]);

        $this->post('/sign-in', ['email' => 'gone@uew.edu.gh', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_sign_in_locks_out_after_five_failures(): void
    {
        $this->staff(Role::Officer, ['email' => 'target@uew.edu.gh']);

        foreach (range(1, 5) as $i) {
            $this->post('/sign-in', ['email' => 'target@uew.edu.gh', 'password' => "guess{$i}"]);
        }

        $this->post('/sign-in', ['email' => 'target@uew.edu.gh', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Too many sign-in attempts. Try again in 15 minute(s).']);
        $this->assertGuest();
    }

    public function test_a_user_deactivated_mid_session_is_signed_out_on_the_next_request(): void
    {
        $user = $this->signInAs(Role::Officer);
        $this->get('/workspace')->assertOk();

        User::whereKey($user->id)->update(['is_active' => false]);
        $this->actingAs($user->fresh());

        $this->get('/workspace/projects')->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_sign_out_invalidates_the_session(): void
    {
        $user = $this->signInAs(Role::Viewer);

        $this->post('/sign-out')->assertRedirect(route('site.home'));

        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.logout', 'user_id' => $user->id]);
    }

    public function test_signed_in_staff_visiting_sign_in_go_to_the_workspace(): void
    {
        $this->signInAs(Role::Viewer);

        $this->get('/sign-in')->assertRedirect(route('workspace.dashboard'));
    }

    public function test_staff_can_change_their_own_password(): void
    {
        $user = $this->signInAs(Role::Intern);

        $this->put('/workspace/account/password', [
            'current_password' => 'wrong', 'password' => 'new-secret-123', 'password_confirmation' => 'new-secret-123',
        ])->assertSessionHasErrors('current_password');

        $this->put('/workspace/account/password', [
            'current_password' => 'password', 'password' => 'new-secret-123', 'password_confirmation' => 'new-secret-123',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('new-secret-123', $user->fresh()->password));
    }
}

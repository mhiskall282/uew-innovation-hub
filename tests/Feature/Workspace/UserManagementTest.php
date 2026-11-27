<?php

namespace Tests\Feature\Workspace;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_hub_director_creates_accounts_for_every_role_including_intern(): void
    {
        $this->signInAs(Role::SuperAdmin);

        $this->post('/workspace/users', [
            'name' => 'Kofi Intern',
            'email' => 'Kofi.Intern@UEW.edu.gh',
            'department' => 'Communications',
            'role' => 'intern',
            'password' => 'temporary-pass-1',
            'password_confirmation' => 'temporary-pass-1',
        ])->assertRedirect(route('workspace.users.index'));

        $user = User::where('email', 'kofi.intern@uew.edu.gh')->sole();
        $this->assertSame(Role::Intern, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.created', 'subject_id' => $user->id]);
    }

    public function test_invalid_roles_and_unconfirmed_passwords_are_rejected(): void
    {
        $this->signInAs(Role::SuperAdmin);

        $this->post('/workspace/users', [
            'name' => 'X', 'email' => 'x@uew.edu.gh', 'role' => 'god_mode',
            'password' => 'temporary-pass-1', 'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['role', 'password']);
    }

    public function test_role_cannot_be_mass_assigned_through_other_forms(): void
    {
        $officer = $this->signInAs(Role::Officer);

        // Extra fields on an unrelated form are ignored.
        $this->put('/workspace/profile/password', [
            'current_password' => 'password', 'password' => 'another-pass-9', 'password_confirmation' => 'another-pass-9',
            'role' => 'super_admin', 'is_active' => 1,
        ]);

        $this->assertSame(Role::Officer, $officer->fresh()->role);

        // And officers cannot reach user management at all.
        $this->put("/workspace/users/{$officer->id}", ['name' => 'Me', 'email' => $officer->email, 'role' => 'super_admin'])->assertForbidden();
        $this->assertSame(Role::Officer, $officer->fresh()->role);
    }

    public function test_deactivating_signs_the_user_out_and_blocks_sign_in(): void
    {
        $this->signInAs(Role::SuperAdmin);
        $member = $this->staff(Role::Officer);

        $this->patch("/workspace/users/{$member->id}/toggle")->assertRedirect();
        $this->assertFalse($member->fresh()->is_active);

        $this->patch("/workspace/users/{$member->id}/toggle");
        $this->assertTrue($member->fresh()->is_active);
    }

    public function test_admins_cannot_deactivate_or_delete_themselves(): void
    {
        $admin = $this->signInAs(Role::SuperAdmin);

        $this->patch("/workspace/users/{$admin->id}/toggle")->assertForbidden();
        $this->delete("/workspace/users/{$admin->id}")->assertForbidden();
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_the_last_active_hub_director_cannot_be_demoted(): void
    {
        $admin = $this->signInAs(Role::SuperAdmin);

        $this->put("/workspace/users/{$admin->id}", ['name' => $admin->name, 'email' => $admin->email, 'role' => 'officer'])
            ->assertSessionHasErrors('role');
        $this->assertSame(Role::SuperAdmin, $admin->fresh()->role);

        // With a second Director, the change is allowed, but only made by the other
        // Director: nobody can change their own role.
        $second = $this->staff(Role::SuperAdmin);
        $this->actingAs($second)->put("/workspace/users/{$admin->id}", ['name' => $admin->name, 'email' => $admin->email, 'role' => 'officer'])
            ->assertSessionHasNoErrors();
        $this->assertSame(Role::Officer, $admin->fresh()->role);
    }

    public function test_password_reset_by_admin_rotates_the_remember_token(): void
    {
        $this->signInAs(Role::SuperAdmin);
        $member = $this->staff(Role::Officer);
        $token = $member->remember_token;

        $this->put("/workspace/users/{$member->id}", [
            'name' => $member->name, 'email' => $member->email, 'role' => 'officer',
            'password' => 'brand-new-pass-1', 'password_confirmation' => 'brand-new-pass-1',
        ])->assertSessionHasNoErrors();

        $this->assertNotSame($token, $member->fresh()->remember_token);
    }

    public function test_partners_linked_to_projects_cannot_be_deleted(): void
    {
        $this->signInAs(Role::Officer);
        $partner = Organization::factory()->create();
        Project::factory()->create()->organizations()->attach($partner, ['role' => 'Partner']);
        $orphan = Organization::factory()->create();

        $this->delete("/workspace/organizations/{$partner->id}")->assertSessionHasErrors('organization');
        $this->delete("/workspace/organizations/{$orphan->id}")->assertRedirect(route('workspace.organizations.index'));

        $this->assertModelExists($partner);
        $this->assertModelMissing($orphan);
    }

    public function test_audit_log_is_searchable_by_hub_director(): void
    {
        $this->signInAs(Role::SuperAdmin);
        $this->post('/workspace/organizations', ['name' => 'Kente Labs', 'type' => 'Industry Partner']);

        $this->get('/workspace/audit?area=organization')->assertOk()->assertSee('Kente Labs');
        $this->get('/workspace/audit?area=auth')->assertDontSee('Kente Labs');
    }
}

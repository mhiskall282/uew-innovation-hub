<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function signInAs(string $role): static
    {
        return $this->actingAs(User::factory()->create(['role' => $role]));
    }

    public function test_guests_are_redirected_from_admin_routes(): void
    {
        $this->get('/admin/users')->assertRedirect('/login');
        $this->get('/admin/grants')->assertRedirect('/login');
        $this->get('/projects/create')->assertRedirect('/login');
    }

    public function test_viewer_cannot_reach_staff_areas(): void
    {
        $this->signInAs('viewer');

        foreach (['/admin/users', '/admin/activity-logs', '/admin/grants', '/admin/posts', '/projects/create'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_intern_can_manage_posts_but_not_grants_or_users(): void
    {
        $this->signInAs('intern');

        $this->get('/admin/posts')->assertOk();
        $this->get('/admin/grants')->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
    }

    public function test_reviewer_can_open_grants_but_not_projects_or_posts(): void
    {
        $this->signInAs('reviewer');

        $this->get('/admin/grants')->assertOk();
        $this->get('/projects/create')->assertForbidden();
        $this->get('/admin/posts')->assertForbidden();
    }

    public function test_only_super_admin_sees_audit_logs(): void
    {
        $this->signInAs('officer')->get('/admin/activity-logs')->assertForbidden();
        $this->signInAs('super_admin')->get('/admin/activity-logs')->assertOk();
    }

    public function test_security_headers_are_present(): void
    {
        $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }
}

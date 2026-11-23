<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_users_directory(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@uew.edu.gh',
            'role' => 'super_admin',
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($admin)->get('/admin/users');
        $response->assertStatus(200);
        $response->assertSee('Access Control & Staff Directory', false);
    }

    public function test_super_admin_can_provision_new_staff(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@uew.edu.gh',
            'role' => 'super_admin',
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'New Researcher',
            'email' => 'new.researcher@uew.edu.gh',
            'role' => 'officer',
            'department' => 'Department of Physics Education',
            'password' => 'securepass123',
        ]);

        $response->assertRedirect('/admin/users');
        $this->assertDatabaseHas('users', [
            'email' => 'new.researcher@uew.edu.gh',
            'role' => 'officer',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.created',
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Institutional Access');
    }

    public function test_users_can_authenticate_using_valid_credentials(): void
    {
        $user = User::create([
            'name' => 'Officer Test',
            'email' => 'officer.test@uew.edu.gh',
            'role' => 'officer',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'officer.test@uew.edu.gh',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::create([
            'name' => 'Officer Test',
            'email' => 'officer.test@uew.edu.gh',
            'role' => 'officer',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'officer.test@uew.edu.gh',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_user_can_logout(): void
    {
        $user = User::create([
            'name' => 'Officer Test',
            'email' => 'officer.test@uew.edu.gh',
            'role' => 'officer',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }
}

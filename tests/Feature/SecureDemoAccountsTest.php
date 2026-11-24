<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Console\Commands\SecureDemoAccounts;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecureDemoAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_passwords_are_replaced_with_a_strong_seed_password(): void
    {
        $weak = User::factory()->create(['password' => 'password']);
        $fine = User::factory()->create(['password' => 'Another-Strong-99']);
        config(['hub.seed_password' => 'Tester-Pass-2026x']);

        $this->artisan('hub:secure-demo-accounts')->assertSuccessful();

        $this->assertTrue(Hash::check('Tester-Pass-2026x', $weak->fresh()->password));
        $this->assertTrue(Hash::check('Another-Strong-99', $fine->fresh()->password));
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.password_rotated', 'subject_id' => $weak->id]);
    }

    public function test_nothing_changes_without_a_strong_seed_password(): void
    {
        $weak = User::factory()->create(['password' => 'password']);
        config(['hub.seed_password' => 'password']);

        $this->artisan('hub:secure-demo-accounts')->assertFailed();
        $this->assertTrue(Hash::check('password', $weak->fresh()->password));
    }

    public function test_strength_rules(): void
    {
        $this->assertFalse(SecureDemoAccounts::isStrong('password'));
        $this->assertFalse(SecureDemoAccounts::isStrong('alllowercase1234'));
        $this->assertFalse(SecureDemoAccounts::isStrong(null));
        $this->assertTrue(SecureDemoAccounts::isStrong('Uew-H5KM1PNkpEk5Gi'));
    }
}

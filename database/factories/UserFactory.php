<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'role' => Role::Viewer,
            'department' => fake()->randomElement(['Department of ICT Education', 'Department of Basic Education', 'Innovation Hub Office']),
            'is_active' => true,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function role(Role $role): static
    {
        return $this->state(fn () => ['role' => $role]);
    }

    public function admin(): static
    {
        return $this->role(Role::SuperAdmin);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /** A verified, onboarded public member. */
    public function member(): static
    {
        return $this->state(fn () => [
            'role' => Role::Member,
            'department' => null,
            'phone' => '+233 24 '.fake()->numerify('### ####'),
            'affiliation' => 'student',
            'institution' => 'University of Education, Winneba',
            'gender' => fake()->randomElement(['female', 'male']),
            'date_of_birth' => fake()->dateTimeBetween('-35 years', '-18 years')->format('Y-m-d'),
            'region' => fake()->randomElement(['Central', 'Greater Accra', 'Ashanti', 'Volta']),
            'education_level' => 'undergraduate',
            'disability' => 'no',
            'interests' => ['STEM'],
            'marketing_opt_in' => true,
            'onboarded_at' => now(),
        ]);
    }

    public function unonboarded(): static
    {
        return $this->state(fn () => ['onboarded_at' => null]);
    }
}

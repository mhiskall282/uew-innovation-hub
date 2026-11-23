<?php

namespace Database\Factories;

use App\Enums\GrantStatus;
use App\Models\GrantApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GrantApplication>
 */
class GrantApplicationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'applicant_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+233 24 '.fake()->numerify('### ####'),
            'institution_or_org' => 'Department of '.fake()->word().' Education, UEW',
            'project_title' => fake()->sentence(6),
            'category' => fake()->randomElement(config('hub.focus_areas')),
            'funding_requested' => fake()->randomFloat(2, 1000, 50000),
            'summary' => fake()->paragraph(4),
            'status' => GrantStatus::Submitted,
        ];
    }

    public function status(GrantStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}

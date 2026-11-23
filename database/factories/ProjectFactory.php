<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $target = fake()->numberBetween(200, 10000);
        $start = fake()->dateTimeBetween('-3 years', 'now');

        return [
            'title' => fake()->unique()->sentence(5),
            'summary' => fake()->paragraph(3),
            'category' => fake()->randomElement(config('hub.focus_areas')),
            'status' => ProjectStatus::Active,
            'start_date' => $start,
            'end_date' => (clone $start)->modify('+18 months'),
            'beneficiaries_target' => $target,
            'beneficiaries_reached' => fake()->numberBetween(0, $target),
            'grant_amount' => fake()->randomFloat(2, 10000, 500000),
            'lead_researcher' => 'Dr. '.fake()->name(),
            'department' => 'Department of '.fake()->randomElement(['ICT Education', 'Science Education', 'Special Education']),
            'is_public' => true,
        ];
    }

    public function internal(): static
    {
        return $this->state(fn () => ['is_public' => false]);
    }

    public function status(ProjectStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}

<?php

namespace Database\Factories;

use App\Enums\ProgramStatus;
use App\Enums\ProgramType;
use App\Models\Program;
use App\Support\FormSchema;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(4),
            'type' => ProgramType::Grant,
            'focus_area' => 'STEM',
            'summary' => fake()->sentence(16),
            'description' => fake()->paragraphs(2, true),
            'opens_at' => now()->subWeek(),
            'closes_at' => now()->addMonth(),
            'form_schema' => FormSchema::starter(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Program $program) {
            $program->status ??= ProgramStatus::Published;
        });
    }

    public function published(): static
    {
        return $this->state(fn () => [])->afterMaking(fn (Program $p) => $p->status = ProgramStatus::Published);
    }

    public function draft(): static
    {
        return $this->afterMaking(fn (Program $p) => $p->status = ProgramStatus::Draft);
    }

    public function closed(): static
    {
        return $this->state(fn () => ['closes_at' => now()->subDay()]);
    }

    public function upcoming(): static
    {
        return $this->state(fn () => ['opens_at' => now()->addWeek(), 'closes_at' => now()->addMonths(2)]);
    }

    /** @param list<array<string, mixed>> $fields */
    public function withFields(array $fields): static
    {
        return $this->state(fn () => ['form_schema' => FormSchema::normalize($fields)]);
    }
}

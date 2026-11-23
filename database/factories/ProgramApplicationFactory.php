<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Program;
use App\Models\ProgramApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgramApplication>
 */
class ProgramApplicationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ProgramApplication $a) {
            $a->program_id ??= Program::factory()->create()->id;
            $a->user_id ??= User::factory()->member()->create()->id;
            $a->answers ??= ['title' => 'Solar kits', 'description' => 'A detailed idea.'];
            if ($a->getAttributes()['status'] === 'draft' && ! $a->submitted_at) {
                $a->status = ApplicationStatus::Submitted;
                $a->submitted_at = now()->subDay();
            }
        });
    }

    public function between(Program $program, User $user): static
    {
        return $this->afterMaking(function (ProgramApplication $a) use ($program, $user) {
            $a->program_id = $program->id;
            $a->user_id = $user->id;
        });
    }

    public function status(ApplicationStatus $status): static
    {
        return $this->afterMaking(function (ProgramApplication $a) use ($status) {
            $a->status = $status;
            $a->submitted_at = $status === ApplicationStatus::Draft ? null : now()->subDay();
        });
    }
}

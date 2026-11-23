<?php

namespace Database\Factories;

use App\Models\Publication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Publication>
 */
class PublicationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(8),
            'type' => fake()->randomElement(config('hub.publication_types')),
            'authors' => fake()->name().', '.fake()->name(),
            'abstract' => fake()->paragraph(),
            'publish_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'journal_name' => fake()->words(4, true),
            'doi_url' => null,
            'pdf_file_path' => null,
        ];
    }
}

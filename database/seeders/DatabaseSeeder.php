<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Default pages are always created (they never overwrite edits).
 * Demo accounts and sample content are for local development only.
 *
 * In production create the first administrator with `php artisan
 * hub:create-admin` instead (the Docker entrypoint does this).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PageSeeder::class);

        if (app()->isProduction() && ! config('hub.seed_demo_data')) {
            $this->command?->warn('Skipping demo data in production. Set SEED_DEMO_DATA=true to override.');

            return;
        }

        $this->call([DemoContentSeeder::class, DemoProgrammesSeeder::class]);
    }
}

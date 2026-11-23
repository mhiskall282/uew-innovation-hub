<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Creates the first Hub Director account. Safe to run on every deploy: it
 * does nothing when an active Hub Director already exists.
 *
 *   php artisan hub:create-admin --email=director@uew.edu.gh --name="Hub Director"
 *
 * Values fall back to ADMIN_EMAIL / ADMIN_NAME / ADMIN_PASSWORD. If no
 * password is supplied a strong one is generated and printed once.
 */
class CreateAdministrator extends Command
{
    protected $signature = 'hub:create-admin
        {--email= : Email address for the administrator}
        {--name= : Display name}
        {--password= : Password (generated if omitted)}';

    protected $description = 'Create the first Hub Director (super admin) account if none exists';

    public function handle(AuditLogger $audit): int
    {
        if (User::where('role', Role::SuperAdmin->value)->where('is_active', true)->exists()) {
            $this->components->info('An active Hub Director already exists. Nothing to do.');

            return self::SUCCESS;
        }

        $email = Str::lower(trim((string) ($this->option('email') ?: config('hub.admin.email'))));
        $name = (string) ($this->option('name') ?: config('hub.admin.name'));
        $password = (string) ($this->option('password') ?: config('hub.admin.password'));
        $generated = false;

        if ($email === '') {
            $this->components->error('Provide --email or set ADMIN_EMAIL.');

            return self::FAILURE;
        }

        if ($password === '') {
            $password = Str::password(20);
            $generated = true;
        }

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            [
                'email' => ['required', 'email', 'unique:users,email'],
                // Generated passwords are 20 random characters; only check ones a person chose.
                'password' => $generated ? ['required'] : [Password::min(12)->letters()->mixedCase()->numbers()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
        $user->role = Role::SuperAdmin;
        $user->is_active = true;
        $user->save();

        $audit->record('user.created', "Created first Hub Director account for {$user->name} from the command line", $user);

        $this->components->info("Hub Director created: {$email}");

        if ($generated) {
            $this->components->warn('Generated password (shown once, store it securely and change it after signing in):');
            $this->line("  {$password}");
        }

        return self::SUCCESS;
    }
}

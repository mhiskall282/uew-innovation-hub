<?php

namespace Tests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;

abstract class TestCase extends BaseTestCase
{
    protected function staff(Role $role = Role::Officer, array $attributes = []): User
    {
        return User::factory()->role($role)->create($attributes);
    }

    protected function signInAs(Role $role, array $attributes = []): User
    {
        $user = $this->staff($role, $attributes);
        $this->actingAs($user);

        return $user;
    }

    /** A minimal valid PDF upload. */
    protected function pdf(string $name = 'document.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF");
    }
}

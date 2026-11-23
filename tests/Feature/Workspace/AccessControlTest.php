<?php

namespace Tests\Feature\Workspace;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The full role x page matrix. If a permission changes in App\Enums\Role,
 * this test must be updated deliberately.
 */
class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = [
        'dashboard' => '/workspace',
        'reports' => '/workspace/reports',
        'programmes' => '/workspace/programmes',
        'new programme' => '/workspace/programmes/create',
        'applications' => '/workspace/applications',
        'members' => '/workspace/members',
        'broadcasts' => '/workspace/broadcasts',
        'projects' => '/workspace/projects',
        'new project' => '/workspace/projects/create',
        'export' => '/workspace/projects/export',
        'partners' => '/workspace/organizations',
        'new partner' => '/workspace/organizations/create',
        'publications' => '/workspace/publications',
        'grants' => '/workspace/grants',
        'posts' => '/workspace/posts',
        'new post' => '/workspace/posts/create',
        'pages' => '/workspace/pages',
        'enquiries' => '/workspace/messages',
        'users' => '/workspace/users',
        'settings' => '/workspace/settings',
        'templates' => '/workspace/email-templates',
        'audit' => '/workspace/audit',
        'account' => '/workspace/account',
    ];

    private const STAFF_COMMON = ['dashboard', 'projects', 'partners', 'account'];

    /** Pages each role may open; everything else must be 403. */
    private const ALLOWED = [
        'super_admin' => '*',
        'officer' => ['reports', 'programmes', 'new programme', 'applications', 'members', 'broadcasts', 'new project', 'export',
            'new partner', 'publications', 'grants', 'posts', 'new post', 'pages', 'enquiries'],
        'reviewer' => ['reports', 'applications', 'export', 'grants'],
        'intern' => ['posts', 'new post'],
        'viewer' => [],
        'member' => null, // no workspace at all
    ];

    public static function roles(): array
    {
        return collect(Role::cases())->mapWithKeys(fn (Role $r) => [$r->value => [$r]])->all();
    }

    #[DataProvider('roles')]
    public function test_role_can_reach_exactly_its_pages(Role $role): void
    {
        $this->signInAs($role, $role === Role::Member ? ['onboarded_at' => now()] : []);

        $allowed = self::ALLOWED[$role->value];
        $allowed = $allowed === '*' ? array_keys(self::PAGES) : ($allowed === null ? [] : array_merge(self::STAFF_COMMON, $allowed));

        foreach (self::PAGES as $name => $url) {
            $expected = in_array($name, $allowed, true) ? 200 : 403;
            $this->get($url)->assertStatus($expected, "{$role->value} → {$name} ({$url}) should be {$expected}");
        }
    }

    public function test_guests_are_sent_to_sign_in_for_every_workspace_page(): void
    {
        foreach (self::PAGES as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_sidebar_only_shows_links_the_role_can_use(): void
    {
        $this->signInAs(Role::Intern);

        $this->get('/workspace')
            ->assertSee('News &amp; posts', false)
            ->assertDontSee('Applications to review')
            ->assertDontSee('Staff accounts')
            ->assertDontSee('Site settings')
            ->assertDontSee('Audit log')
            ->assertDontSee('Members');
    }

    public function test_workspace_pages_are_not_indexed_by_search_engines(): void
    {
        $this->signInAs(Role::Viewer);

        $this->get('/workspace')->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }
}

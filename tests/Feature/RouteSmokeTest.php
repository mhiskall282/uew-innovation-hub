<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Broadcast;
use App\Models\ContactMessage;
use App\Models\GrantApplication;
use App\Models\Organization;
use App\Models\Page;
use App\Models\Post;
use App\Models\Program;
use App\Models\ProgramApplication;
use App\Models\ProgressReport;
use App\Models\Project;
use App\Models\Publication;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Requests every named GET route as a guest and as each role, using the demo
 * data, and fails on any server error. Permission denials (403), redirects and
 * missing files (404) are expected outcomes; a 500 never is.
 */
class RouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->seed(DatabaseSeeder::class);

        ContactMessage::query()->exists() || ContactMessage::forceCreate([
            'name' => 'Kofi Mensah', 'email' => 'kofi@example.com', 'subject' => 'Partnership', 'message' => 'We would like to partner with the Hub.',
        ]);
        Broadcast::query()->exists() || Broadcast::forceCreate([
            'subject' => 'Welcome', 'body' => 'Hello members', 'audience' => [], 'created_by' => User::where('email', 'admin@uew.edu.gh')->value('id'),
        ]);
    }

    /** @return array<string, array{0: ?string}> */
    public static function actors(): array
    {
        return [
            'guest' => [null],
            'super admin' => ['admin@uew.edu.gh'],
            'officer' => ['officer@uew.edu.gh'],
            'reviewer' => ['reviewer@uew.edu.gh'],
            'intern' => ['intern@uew.edu.gh'],
            'viewer' => ['viewer@uew.edu.gh'],
            'member' => ['member@uew.edu.gh'],
            'member awaiting onboarding' => ['newmember@uew.edu.gh'],
        ];
    }

    #[DataProvider('actors')]
    public function test_no_get_route_returns_a_server_error(?string $email): void
    {
        $user = $email ? User::where('email', $email)->sole() : null;
        $failures = [];
        $urls = $this->urls($user);

        $this->assertGreaterThan(85, count($urls), 'Most routes should resolve against the demo data.');

        foreach ($urls as $name => $url) {
            $this->app['auth']->forgetGuards();
            if ($user) {
                $this->actingAs($user->fresh());
            }

            $status = $this->get($url)->getStatusCode();
            if ($status >= 500) {
                $failures[] = "{$name} ({$url}) returned {$status}";
            }
            // The super admin holds every permission, so workspace pages must not refuse them.
            if ($email === 'admin@uew.edu.gh' && str_starts_with($name, 'workspace.') && $status === 403) {
                $failures[] = "{$name} ({$url}) refused the super admin";
            }
            if (getenv('SMOKE_DEBUG')) {
                fwrite(STDERR, "[{$email}] {$status} {$name}\n");
            }
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }

    /** @return array<string, string> route name => URL */
    private function urls(?User $user): array
    {
        $ownApplication = $user ? ProgramApplication::where('user_id', $user->id)->first() : null;

        $bindings = [
            'post' => Post::query()->first(),
            'project' => Project::query()->first(),
            'publication' => Publication::query()->first(),
            'program' => Program::query()->first(),
            'member' => User::query()->members()->first(),
            'message' => ContactMessage::query()->first(),
            'organization' => Organization::query()->first(),
            'page' => Page::query()->first(),
            'broadcast' => Broadcast::query()->first(),
            'template' => 'application_submitted',
            'user' => User::query()->first(),
            'key' => 'document',
        ];

        $urls = [];

        /** @var Route $route */
        foreach (Router::getRoutes() as $route) {
            $name = $route->getName();
            if (! $name || ! in_array('GET', $route->methods(), true) || str_starts_with($name, 'ignition') || str_starts_with($name, 'storage')) {
                continue;
            }

            $params = [];
            foreach ($route->parameterNames() as $param) {
                $value = match (true) {
                    $param === 'application' && str_starts_with($name, 'workspace.grants') => GrantApplication::query()->first(),
                    $param === 'application' && str_starts_with($name, 'portal.') => $ownApplication ?? ProgramApplication::query()->first(),
                    $param === 'application' => ProgramApplication::query()->first(),
                    $param === 'report' && str_starts_with($name, 'portal.') => ($user ? ProgressReport::where('user_id', $user->id)->first() : null) ?? ProgressReport::query()->first(),
                    $param === 'report' => ProgressReport::query()->first(),
                    $param === 'user' && str_starts_with($name, 'site.community') => User::query()->onLeaderboard()->first(),
                    $param === 'id' => $user?->notifications()->value('id'),
                    default => $bindings[$param] ?? null,
                };

                if ($value === null) {
                    continue 2; // nothing to bind in the demo data
                }
                $params[$param] = $value;
            }

            $urls[$name] = $name === 'site.unsubscribe'
                ? URL::signedRoute($name, $params)
                : route($name, $params);
        }

        return $urls;
    }
}

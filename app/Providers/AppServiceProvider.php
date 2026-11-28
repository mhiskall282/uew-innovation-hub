<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\Permission;
use App\Models\User;
use App\Services\MailSettings;
use App\Services\SettingsService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One settings instance per request so views and controllers share the memoised copy.
        $this->app->singleton(SettingsService::class);
    }

    public function boot(): void
    {
        $this->registerPermissionGates();
        $this->registerRateLimiters();

        // /up (health check) also proves the database answers, so a host such as Render
        // marks the service unhealthy when the database is gone instead of serving errors.
        Event::listen(DiagnosingHealth::class, fn () => DB::select('select 1'));

        // Outgoing mail can be configured from the workspace (Site settings > E-mail).
        if (! $this->app->runningUnitTests()) {
            $this->app->make(MailSettings::class)->apply();
        }

        // Development and testing safety net: send every e-mail to one inbox so
        // demo accounts (real @uew.edu.gh addresses) never receive test mail.
        // Ignored in production.
        if (! $this->app->isProduction() && ($alwaysTo = config('mail.always_to'))) {
            Mail::alwaysTo($alwaysTo);
        }

        // Catch N+1 queries during development and tests.
        Model::preventLazyLoading(! $this->app->isProduction());

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(12)->letters()->mixedCase()->numbers()
            : Password::min(8));

        if ($this->app->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceHttps();
        }

        Paginator::defaultView('pagination.default');
        Paginator::defaultSimpleView('pagination.simple');
    }

    /**
     * Every Permission case becomes a gate: `can:projects.manage`,
     * `@can('content.publish')`, `Gate::allows('audit.view')`.
     */
    private function registerPermissionGates(): void
    {
        foreach (Permission::cases() as $permission) {
            Gate::define($permission->value, fn (User $user) => $user->hasPermission($permission));
        }
    }

    private function registerRateLimiters(): void
    {
        $perIp = fn (int $minute, int $day) => fn (Request $request) => [
            Limit::perMinute($minute)->by($request->ip()),
            Limit::perDay($day)->by($request->ip()),
        ];

        // Shared by every API version (config/api.php, default 60 per minute per IP).
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(max(1, (int) config('api.rate_limit_per_minute', 60)))->by($request->ip()));
        RateLimiter::for('contact-messages', $perIp(3, 20));
        RateLimiter::for('registrations', $perIp(5, 30));
        RateLimiter::for('password-codes', fn (Request $request) => [
            Limit::perMinute(3)->by($request->ip()),
            Limit::perHour(10)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
        ]);
    }
}

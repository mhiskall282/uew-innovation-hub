<?php

namespace Tests\Feature;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_responses_carry_security_headers_and_a_strict_csp(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringNotContainsString('cdn.', $csp);
    }

    public function test_api_responses_carry_security_headers(): void
    {
        $this->getJson('/api/v1')->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_health_check_is_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_forms_are_csrf_protected(): void
    {
        // The testing kernel disables CSRF only when explicitly asked; verify the middleware is active.
        $this->assertContains(
            ValidateCsrfToken::class,
            app(Kernel::class)->getMiddlewareGroups()['web'],
        );
    }
}

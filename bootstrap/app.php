<?php

use App\Http\Middleware\ApiVersion;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\EnsureOnboarded;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => EnsureAccountIsActive::class,
            'verified.email' => EnsureEmailIsVerified::class,
            'onboarded' => EnsureOnboarded::class,
            'api.version' => ApiVersion::class,
        ]);

        $middleware->appendToGroup('web', SecurityHeaders::class);

        // RFC 8058 one-click unsubscribe: mail providers POST without a CSRF
        // token. The signed URL authorises these requests instead.
        $middleware->validateCsrfTokens(except: ['unsubscribe/*']);
        $middleware->appendToGroup('api', SecurityHeaders::class);

        // Behind Render/Vercel/any load balancer set TRUSTED_PROXIES=* so
        // client IPs (rate limiting, audit log) and HTTPS detection are correct.
        $trusted = env('TRUSTED_PROXIES');
        if ($trusted) {
            $middleware->trustProxies(at: $trusted === '*' ? '*' : array_map('trim', explode(',', $trusted)));
        }

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()?->homeUrl() ?? route('site.home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // The public API always answers in the same JSON envelope.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        // Expired, tampered or already-replaced e-mail links get a helpful page, not a bare 403.
        $exceptions->render(function (InvalidSignatureException $e, Request $request) {
            return $request->is('api/*') ? null : response()->view('auth.link-expired', [], 403);
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            [$status, $message, $errors] = match (true) {
                $e instanceof ValidationException => [422, 'The given data was invalid.', $e->errors()],
                $e instanceof ModelNotFoundException, $e instanceof NotFoundHttpException => [404, 'Resource not found.', null],
                $e instanceof AuthenticationException => [401, 'Unauthenticated.', null],
                $e instanceof AuthorizationException => [403, 'This action is unauthorized.', null],
                $e instanceof ThrottleRequestsException => [429, 'Too many requests. Please slow down.', null],
                $e instanceof HttpExceptionInterface => [$e->getStatusCode(), $e->getMessage() ?: 'Request failed.', null],
                default => [500, config('app.debug') ? $e->getMessage() : 'Server error.', null],
            };

            $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : [];

            // v2 and later: { "error": { status, code, message, details } } with a stable,
            // machine-readable code. v1 keeps its original { success, message, errors } shape.
            if ($request->is('api/v2', 'api/v2/*')) {
                $error = ['status' => $status, 'code' => match ($status) {
                    400 => 'bad_request',
                    401 => 'unauthenticated',
                    403 => 'forbidden',
                    404 => 'not_found',
                    405 => 'method_not_allowed',
                    422 => 'validation_failed',
                    429 => 'rate_limited',
                    500 => 'server_error',
                    503 => 'service_unavailable',
                    default => 'http_error',
                }, 'message' => $message];
                if ($errors) {
                    $error['details'] = $errors;
                }

                return response()->json(['error' => $error], $status, $headers);
            }

            $payload = ['success' => false, 'message' => $message];
            if ($errors) {
                $payload['errors'] = $errors;
            }

            return response()->json($payload, $status, $headers);
        });
    })->create();

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** New members finish their profile and interests before using the portal. */
final class EnsureOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->needsOnboarding()) {
            return redirect()->guest(route('onboarding.show'));
        }

        return $next($request);
    }
}

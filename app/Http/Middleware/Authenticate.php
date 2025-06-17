<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

/**
 * Handle authentication for protected routes.
 *
 * This middleware relies on Laravel's base Authenticate middleware and only
 * overrides the redirect logic so that users who are not authenticated are
 * redirected to the login route on web requests or receive a JSON 401
 * response on API requests.
 */
class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not
     * authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if (! $request->expectsJson()) {
            // Redirect unauthenticated web users to the login route
            return route('login');
        }

        // For API requests that expect JSON, Laravel will automatically return
        // a 401 JSON response, so we return null to fall back to the default
        // behaviour defined in the parent class.
        return null;
    }
}

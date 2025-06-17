<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Exempt all API routes from CSRF since they are typically stateless
        'api/*',
        // Add additional URIs here as needed, e.g. 'payment/webhook',
    ];
}

<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Middleware\TrustProxies as Middleware;


class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * If you are using Stripe / AWS ELB or another load balancer / reverse proxy
     * service, you may need to list their IP ranges here or set it to '*'.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = '*'; // Faire confiance à tous les proxies

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    // Trust typical forwarded headers (FOR, HOST, PORT, and PROTO)
    protected $headers = Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO;
}

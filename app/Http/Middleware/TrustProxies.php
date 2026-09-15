<?php

namespace App\Http\Middleware;

class TrustProxies extends \Illuminate\Http\Middleware\TrustProxies
{
    /**
     * @return array<int, string>|string|null
     */
    protected function proxies(): array|string|null
    {
        $proxies = config('app.trusted_proxies', []);

        return $proxies === [] ? null : $proxies;
    }
}

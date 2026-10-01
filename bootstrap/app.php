<?php

use App\Http\Middleware\PreventAccessDuringSiteMaintenance;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TrustProxies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies as LaravelTrustProxies;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->replace(LaravelTrustProxies::class, TrustProxies::class);
        $middleware->append(SecurityHeaders::class);
        $middleware->appendToGroup('web', PreventAccessDuringSiteMaintenance::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();

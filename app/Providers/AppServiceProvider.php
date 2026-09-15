<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! app()->isProduction()) {
            return;
        }

        $appUrl = rtrim((string) config('app.url'), '/');

        if (! str_starts_with($appUrl, 'https://')) {
            throw new RuntimeException('APP_URL must use HTTPS in production.');
        }

        URL::forceRootUrl($appUrl);
        URL::forceScheme('https');

        if (config('app.debug')) {
            throw new RuntimeException('APP_DEBUG must be false in production.');
        }

        if (! config('session.secure')) {
            throw new RuntimeException('SESSION_SECURE_COOKIE must be true in production.');
        }

        $siteKey = (string) config('filament-turnstile.site_key');
        $secretKey = (string) config('filament-turnstile.secret_key');

        if (
            $siteKey === ''
            || $secretKey === ''
            || str_starts_with($siteKey, '1x000')
            || str_starts_with($secretKey, '1x000')
        ) {
            throw new RuntimeException('Production Cloudflare Turnstile keys are required.');
        }
    }
}

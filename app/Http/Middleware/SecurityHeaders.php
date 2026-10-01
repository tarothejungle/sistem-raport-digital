<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Livewire serves source maps through a dynamic route, outside public/.
        if (config('app.env') === 'production' && preg_match('/\.map$/i', $request->path())) {
            return response('', 404);
        }

        if (config('app.env') === 'production' && ! $request->isSecure() && ! $request->is('up')) {
            return redirect(rtrim((string) config('app.url'), '/').$request->getRequestUri(), 301);
        }

        $response = $next($request);

        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }

        $response->headers->remove('X-Powered-By');
        $response->headers->remove('X-XSS-Protection');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->is('admin', 'admin/*', 'maintenance', 'maintenance/*', 'livewire-*/*', 'livewire/*')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        $viteOrigin = $this->viteOrigin();
        $scriptSources = "'self' 'unsafe-inline' 'unsafe-eval' https://challenges.cloudflare.com";
        $styleSources = "'self' 'unsafe-inline'";
        $fontSources = "'self' data:";
        $connectSources = "'self' https://challenges.cloudflare.com";

        if ($viteOrigin !== null) {
            $scriptSources .= ' '.$viteOrigin;
            $styleSources .= ' '.$viteOrigin;
            $fontSources .= ' '.$viteOrigin;
            $connectSources .= ' '.$viteOrigin.' '.$this->webSocketOrigin($viteOrigin);
        }

        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'; script-src {$scriptSources}; worker-src 'self' blob:; style-src {$styleSources}; img-src 'self' data: blob: https://ui-avatars.com; font-src {$fontSources}; connect-src {$connectSources}; frame-src 'self' https://challenges.cloudflare.com;",
        );

        if ($request->isSecure() && config('app.env') === 'production') {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains',
            );
        }

        return $response;
    }

    private function viteOrigin(): ?string
    {
        if (config('app.env') === 'production') {
            return null;
        }

        $url = (string) config('app.vite_dev_server_url');
        $parts = parse_url($url);

        if ($url === '' || ! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        if (! in_array($parts['scheme'], ['http', 'https'], true)) {
            return null;
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return $parts['scheme'].'://'.$parts['host'].$port;
    }

    private function webSocketOrigin(string $origin): string
    {
        return str_starts_with($origin, 'https://')
            ? 'wss://'.substr($origin, 8)
            : 'ws://'.substr($origin, 7);
    }
}

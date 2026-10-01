<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ProductionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_public_teacher_sync_route_is_not_exposed(): void
    {
        $this->get('/sync-guru')->assertNotFound();
    }

    public function test_default_seeder_does_not_create_a_known_demo_administrator(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseMissing('user', [
            'username' => 'demo.admin',
        ]);
        $this->assertSame(0, User::query()->where('role', User::ROLE_ADMIN)->count());
    }

    public function test_security_headers_are_added_to_web_responses(): void
    {
        config(['app.vite_dev_server_url' => null]);

        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeader(
                'Content-Security-Policy',
                "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://challenges.cloudflare.com; worker-src 'self' blob:; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: https://ui-avatars.com; font-src 'self' data:; connect-src 'self' https://challenges.cloudflare.com; frame-src 'self' https://challenges.cloudflare.com;",
            )
            ->assertHeaderMissing('X-Powered-By');
    }

    public function test_local_security_headers_allow_the_configured_vite_server(): void
    {
        config(['app.vite_dev_server_url' => 'http://localhost:5173/client-path-is-ignored']);

        $csp = $this->get('/')
            ->assertOk()
            ->headers->get('Content-Security-Policy');

        $this->assertStringContainsString(
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://challenges.cloudflare.com http://localhost:5173;",
            $csp,
        );
        $this->assertStringContainsString(
            "connect-src 'self' https://challenges.cloudflare.com http://localhost:5173 ws://localhost:5173;",
            $csp,
        );
        $this->assertStringContainsString(
            "font-src 'self' data: http://localhost:5173;",
            $csp,
        );
        $this->assertStringNotContainsString('client-path-is-ignored', $csp);
    }

    public function test_production_security_headers_never_allow_the_vite_server(): void
    {
        config([
            'app.env' => 'production',
            'app.vite_dev_server_url' => 'http://localhost:5173',
        ]);

        $request = Request::create('https://raport.test/up', 'GET');
        $response = app(SecurityHeaders::class)->handle(
            $request,
            static fn () => response('healthy'),
        );

        $this->assertStringNotContainsString(
            'localhost:5173',
            $response->headers->get('Content-Security-Policy'),
        );
    }

    public function test_trusted_proxy_generates_https_asset_urls(): void
    {
        $this->assertSame(['127.0.0.1'], config('app.trusted_proxies'));

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeaders([
                'Host' => 'origin.internal',
                'X-Forwarded-Host' => 'raport.test',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('/')
            ->assertOk()
            ->assertSee('src="/logo/logo-baru-dark.png"', false)
            ->assertDontSee('http://raport.test/logo/', false);
    }

    public function test_production_forces_https_urls_when_proxy_headers_are_unavailable(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        config([
            'app.env' => 'production',
            'app.url' => 'https://raport.test',
            'app.debug' => false,
            'session.secure' => true,
            'filament-turnstile.site_key' => 'production-site-key',
            'filament-turnstile.secret_key' => 'production-secret-key',
        ]);

        (new AppServiceProvider($this->app))->boot();

        $this->withServerVariables(['HTTPS' => 'on'])
            ->get('/admin/login')
            ->assertOk()
            ->assertSee('https://raport.test/css/app/raport-theme.css', false)
            ->assertSee('https://raport.test/js/filament/filament/app.js', false)
            ->assertSee('https://raport.test/livewire-', false)
            ->assertDontSee('http://raport.test', false);

        URL::forceRootUrl(null);
        URL::forceScheme(null);
    }

    public function test_production_redirects_inbound_http_to_https(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        config([
            'app.env' => 'production',
            'app.url' => 'https://raport.test',
            'app.debug' => false,
            'session.secure' => true,
            'filament-turnstile.site_key' => 'production-site-key',
            'filament-turnstile.secret_key' => 'production-secret-key',
        ]);

        $request = Request::create('http://raport.test/admin/login', 'GET');
        $response = app(SecurityHeaders::class)->handle(
            $request,
            static fn () => response('insecure'),
        );

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('https://raport.test/admin/login', $response->headers->get('Location'));

        URL::forceRootUrl(null);
        URL::forceScheme(null);
    }

    public function test_production_rejects_an_insecure_asset_url(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        config([
            'app.env' => 'production',
            'app.url' => 'https://raport.test',
            'app.asset_url' => 'http://raport.test',
            'app.debug' => false,
            'session.secure' => true,
            'filament-turnstile.site_key' => 'production-site-key',
            'filament-turnstile.secret_key' => 'production-secret-key',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ASSET_URL must use HTTPS in production or remain empty.');

        (new AppServiceProvider($this->app))->boot();
    }

    public function test_public_and_auth_pages_use_the_project_logo(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('logo/logo-baru-dark.png', false);

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('logo/logo-baru-dark.png', false)
            ->assertSee('logo/logo-baru-light.png', false);
    }

    public function test_admin_panel_uses_project_branding_and_a_fully_collapsible_sidebar(): void
    {
        $panel = Filament::getPanel('admin');
        $brandLogo = view('components.filament-logo')->render();

        $this->assertStringContainsString('/logo/logo-baru-dark.png?v=', $panel->getFavicon());
        $this->assertStringContainsString('logo/logo-baru-dark.png', $brandLogo);
        $this->assertStringContainsString('logo/logo-baru-light.png', $brandLogo);
        $this->assertStringNotContainsString('logo/logo-rapor-', $brandLogo);
        $this->assertTrue($panel->isSidebarFullyCollapsibleOnDesktop());
        $this->assertFalse($panel->isSidebarCollapsibleOnDesktop());
    }
}

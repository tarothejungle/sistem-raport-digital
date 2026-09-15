<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\AppServiceProvider;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
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
            ->assertSee('https://raport.test/logo/logo-rapor-light.png', false);
    }

    public function test_production_forces_https_urls_when_proxy_headers_are_unavailable(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        config([
            'app.url' => 'https://raport.test',
            'app.debug' => false,
            'session.secure' => true,
            'filament-turnstile.site_key' => 'production-site-key',
            'filament-turnstile.secret_key' => 'production-secret-key',
        ]);

        (new AppServiceProvider($this->app))->boot();

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('https://raport.test/css/app/raport-theme.css', false)
            ->assertSee('https://raport.test/js/filament/filament/app.js', false)
            ->assertSee('https://raport.test/livewire-', false)
            ->assertDontSee('http://raport.test', false);

        URL::forceRootUrl(null);
        URL::forceScheme(null);
    }

    public function test_public_and_auth_pages_use_the_project_logo(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('logo/logo-rapor-light.png', false);

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('logo/logo-rapor-dark.png', false)
            ->assertSee('logo/logo-rapor-light.png', false);
    }

    public function test_admin_panel_uses_project_branding_and_a_fully_collapsible_sidebar(): void
    {
        $panel = Filament::getPanel('admin');
        $brandLogo = view('components.filament-logo')->render();

        $this->assertStringEndsWith('/logo/logo-rapor.png', $panel->getFavicon());
        $this->assertStringContainsString('logo/logo-rapor-dark.png', $brandLogo);
        $this->assertStringContainsString('logo/logo-rapor-light.png', $brandLogo);
        $this->assertTrue($panel->isSidebarFullyCollapsibleOnDesktop());
        $this->assertFalse($panel->isSidebarCollapsibleOnDesktop());
    }
}

<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\Auth\ChangePassword;
use App\Filament\Admin\Pages\Auth\EditProfile;
use App\Filament\Admin\Pages\Auth\Login;
use App\Filament\Admin\Pages\Auth\PasswordReset\RequestPasswordReset;
use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Widgets\RaportCommandCenter;
use App\Filament\Admin\Widgets\RaportOverview;
use App\Http\Middleware\PreventAccessDuringSiteMaintenance;
use Filament\Enums\ThemeMode;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsIconAlias;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use MuazzamBuilds\FilamentTurnstile\TurnstilePlugin;
use Zvizvi\FilamentNotificationsTabs\FilamentNotificationsTabsPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->darkMode(true)
            ->defaultThemeMode(ThemeMode::Light)
            ->login(Login::class)
            ->passwordReset(RequestPasswordReset::class)
            ->authPasswordBroker('users')
            ->profile(EditProfile::class, isSimple: false)
            ->userMenuItems([
                'profile' => fn ($action) => $action
                    ->label('Ubah Profil')
                    ->icon('heroicon-o-user-circle')
                    ->sort(10),

                'change-password' => MenuItem::make()
                    ->label('Ganti Kata Sandi')
                    ->icon('heroicon-o-key')
                    ->url(fn (): string => ChangePassword::getUrl())
                    ->sort(20),

                'logout' => fn ($action) => $action
                    ->label('Keluar')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->color('danger')
                    ->url(null)
                    ->postToUrl(false)
                    ->requiresConfirmation()
                    ->modalHeading('Keluar dari akun?')
                    ->modalDescription('Sesi Anda akan diakhiri dan Anda harus masuk kembali untuk mengakses sistem.')
                    ->modalSubmitActionLabel('Ya, keluar')
                    ->modalCancelActionLabel('Batal')
                    ->action(function () {
                        Filament::auth()->logout();

                        session()->invalidate();
                        session()->regenerateToken();

                        return redirect()->to(Filament::getLoginUrl() ?? Filament::getUrl());
                    })
                    ->sort(100),
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->plugins([
                FilamentNotificationsTabsPlugin::make()
                    ->confirmDelete(),
                TurnstilePlugin::make()
                    ->protectLogin(false)
                    ->theme('auto')
                    ->size('flexible')
                    ->language('id'),
            ])
            ->brandName('Sistem Rapor Digital')
            ->brandLogo(fn (): View => view('components.filament-logo'))
            ->brandLogoHeight('2.25rem')
            ->favicon('/logo/logo-baru-dark.png?v='.filemtime(public_path('logo/logo-baru-dark.png')))
            ->homeUrl(fn (): string => '/admin')
            ->sidebarFullyCollapsibleOnDesktop()
            ->sidebarWidth('18rem')
            ->maxContentWidth(Width::SevenExtraLarge)
            ->navigationGroups([
                'Pengaturan',
                'Master Data',
                'Akademik',
                'Portal Siswa',
            ])
            ->colors([
                'primary' => Color::generateV3Palette('#1769ff'),
                'info' => Color::generateV3Palette('#0ea5e9'),
                'success' => Color::generateV3Palette('#13a76b'),
                'warning' => Color::generateV3Palette('#f59e0b'),
                'danger' => Color::generateV3Palette('#ef4444'),
            ])
            ->assets([
                Css::make(
                    'raport-theme',
                    resource_path('css/filament/admin/raport-theme.css'),
                )->html(static fn (): string => asset('css/app/raport-theme.css').'?v='.filemtime(
                    resource_path('css/filament/admin/raport-theme.css'),
                )),

                Js::make(
                    'gamified-login',
                    resource_path('js/gamified-login.js'),
                ),
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                Dashboard::class,
                ChangePassword::class,
            ])
            ->widgets([
                RaportOverview::class,
                RaportCommandCenter::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                PreventAccessDuringSiteMaintenance::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    public function boot(): void
    {
        $sidebarToggleIcon = new HtmlString(<<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="4" width="18" height="16" rx="2.5" />
                <path d="M8.5 4v16" />
            </svg>
            SVG);

        FilamentIcon::register([
            PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON => $sidebarToggleIcon,
            PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON_RTL => $sidebarToggleIcon,
            PanelsIconAlias::SIDEBAR_EXPAND_BUTTON => $sidebarToggleIcon,
            PanelsIconAlias::SIDEBAR_EXPAND_BUTTON_RTL => $sidebarToggleIcon,
            PanelsIconAlias::TOPBAR_OPEN_SIDEBAR_BUTTON => $sidebarToggleIcon,
        ]);

        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_AFTER,
            static fn (): View => view(
                'filament.admin.components.topbar-user-summary',
            ),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_START,
            static fn (): View => view('components.mobile-topbar-brand'),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_NAV_START,
            static fn (): View => view(
                'filament.admin.components.sidebar-context',
            ),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_NAV_END,
            static fn (): View => view(
                'filament.admin.components.input-nilai-navigation',
            ),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            static fn (): View => view('components.mobile-bottom-nav'),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            static fn (): View => view('components.announcement-dialog'),
        );
    }
}

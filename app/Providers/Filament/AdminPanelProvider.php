<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\Auth\ChangePassword;
use App\Filament\Admin\Pages\Auth\EditProfile;
use App\Filament\Admin\Pages\Auth\Login;
use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Widgets\DeleteAvatarModal;
use App\Filament\Admin\Widgets\RaportCommandCenter;
use App\Filament\Admin\Widgets\RaportOverview;
use Filament\Enums\ThemeMode;
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
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Livewire\Livewire;
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
            ->spa()
            ->spaUrlExceptions([
                '*/admin/cetak-rapor/*/pratinjau',
                '*/admin/cetak-rapor/*/unduh',
            ])
            ->login(Login::class)
            ->passwordReset()
            ->authPasswordBroker('users')
            ->profile(EditProfile::class, isSimple: false)
            ->userMenuItems([
                'profile' => fn ($action) => $action
                    ->label('Ubah Profil')
                    ->icon('heroicon-o-user-circle')
                    ->sort(100),

                'change-password' => MenuItem::make()
                    ->label('Ganti Kata Sandi')
                    ->icon('heroicon-o-key')
                    ->url(fn (): string => ChangePassword::getUrl()),

                'logout' => fn ($action) => $action
                    ->label('Keluar')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->color('danger')
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
            ->brandName('')
            ->favicon(asset('favicon.ico'))
            ->homeUrl(fn (): string => '/admin')
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('18rem')
            ->maxContentWidth(Width::SevenExtraLarge)
            ->navigationGroups([
                'Akademik',
                'Master Data',
                'Portal Siswa',
                'Pengaturan',
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
                    'sweetalert2',
                    base_path('node_modules/sweetalert2/dist/sweetalert2.min.css'),
                ),

                Css::make(
                    'raport-theme',
                    resource_path('css/filament/admin/raport-theme.css'),
                )->html(static fn (): string => asset('css/app/raport-theme.css').'?v='.filemtime(
                    resource_path('css/filament/admin/raport-theme.css'),
                )),

                Js::make(
                    'sweetalert2',
                    base_path('node_modules/sweetalert2/dist/sweetalert2.all.min.js'),
                ),

                Js::make(
                    'raport-alerts',
                    resource_path('js/filament/admin/raport-alerts.js'),
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
        Livewire::component('delete-avatar-modal', DeleteAvatarModal::class);

        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_LOGO_AFTER,
            static fn (): View => view(
                'filament.admin.components.topbar-brand',
            ),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_LOGO_AFTER,
            static fn (): View => view(
                'filament.admin.components.mobile-sidebar-brand',
            ),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_AFTER,
            static fn (): View => view(
                'filament.admin.components.topbar-user-summary',
            ),
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
    }
}

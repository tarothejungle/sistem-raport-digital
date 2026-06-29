<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\Auth\Login;
use App\Filament\Admin\Widgets\RaportOverview;
use App\Filament\Admin\Pages\Auth\EditProfile;
use App\Filament\Admin\Pages\Auth\ChangePassword;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Navigation\MenuItem;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\js;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
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
                'profile' => MenuItem::make()
                    ->label('Ubah Profil')
                    ->icon('heroicon-o-user-circle')
                    ->sort(-100),

                'change-password' => MenuItem::make()
                    ->label('Ganti Kata Sandi')
                    ->icon('heroicon-o-key')
                    ->url(fn (): string => ChangePassword::getUrl()),

                'logout' => MenuItem::make()
                    ->label('Keluar')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->color('danger')
                    ->sort(100),
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->brandName('Sistem Raport Digital')
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('18rem')
            ->maxContentWidth(MaxWidth::SevenExtraLarge)
            ->navigationGroups([
                'Pengaturan',
                'Master Data',
                'Akademik',
            ])
            ->colors([
                'primary' => Color::hex('#2563eb'),
            ])
            ->assets([
                Css::make(
                    'sweetalert2',
                    base_path('node_modules/sweetalert2/dist/sweetalert2.min.css'),
                ),

                Css::make(
                    'raport-theme',
                    resource_path('css/filament/admin/raport-theme.css'),
                ),

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
                Pages\Dashboard::class,
                ChangePassword::class,
            ])
            ->widgets([
                RaportOverview::class,
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
        FilamentView::registerRenderHook(
            PanelsRenderHook::USER_MENU_BEFORE,
            static fn (): View => view(
                'filament.admin.components.topbar-user-summary',
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

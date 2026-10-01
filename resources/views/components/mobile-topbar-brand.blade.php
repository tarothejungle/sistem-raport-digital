@auth
    <a
        href="{{ \App\Filament\Admin\Pages\Dashboard::canAccess() ? \App\Filament\Admin\Pages\Dashboard::getUrl() : (\App\Filament\Admin\Resources\NilaiSiswaResource::canViewAny() ? \App\Filament\Admin\Resources\NilaiSiswaResource::getUrl('index') : filament()->getUrl()) }}"
        wire:navigate
        x-data="{}"
        x-init="window.matchMedia('(max-width: 767px)').matches && $store.sidebar.close()"
        class="raport-mobile-topbar-brand"
        aria-label="Sistem Rapor Digital"
    >
        <span class="raport-mobile-topbar-brand__mark" aria-hidden="true">
            <img
                src="/logo/logo-baru-dark.png"
                alt=""
                class="raport-brand-logo raport-brand-logo--light-theme"
            >
            <img
                src="/logo/logo-baru-light.png"
                alt=""
                class="raport-brand-logo raport-brand-logo--dark-theme"
            >
        </span>
        <span>Sistem Rapor Digital</span>
    </a>
@endauth

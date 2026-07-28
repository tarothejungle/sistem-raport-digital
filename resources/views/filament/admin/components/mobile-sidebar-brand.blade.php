<a
    href="{{ filament()->getHomeUrl() ?? url('/admin') }}"
    x-cloak
    x-show="$store.sidebar.isOpen"
    x-transition.opacity.duration.150ms
    class="raport-mobile-sidebar-brand"
    aria-label="Sistem Rapor Digital"
>
    @include('filament.admin.components.brand-logo')
</a>

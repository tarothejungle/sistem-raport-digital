<div
    x-cloak
    x-show="$store.sidebar.isOpen"
    x-transition.opacity.duration.150ms
    class="raport-topbar-brand-wrapper"
>
    @include('filament.admin.components.brand-logo', [
        'class' => 'raport-topbar-brand',
    ])
</div>

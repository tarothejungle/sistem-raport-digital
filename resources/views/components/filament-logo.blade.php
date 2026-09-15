<div
    class="flex items-center gap-3 h-9 overflow-hidden whitespace-nowrap select-none"
    x-data="{}"
    x-cloak
    x-show="! $el.closest('.fi-topbar') || $store.sidebar.isOpen"
>
    <div class="w-9 h-9 flex-shrink-0 flex items-center justify-center">
        <img
            src="{{ asset('logo/logo-rapor-dark.png') }}"
            alt=""
            class="raport-brand-logo raport-brand-logo--light-theme h-full w-full object-contain"
        >
        <img
            src="{{ asset('logo/logo-rapor-light.png') }}"
            alt=""
            class="raport-brand-logo raport-brand-logo--dark-theme h-full w-full object-contain"
        >
    </div>

    <!-- BRAND TEXT (Hidden automatically when sidebar is COLLAPSED) -->
    <div class="flex items-center transition-opacity duration-200 group-data-[sidebar-collapsed]:hidden">
        {{-- Ubah nilai font-size di bawah untuk mengatur kembali ukuran font brand. --}}
        <span class="font-bold text-slate-900 dark:text-white tracking-tight whitespace-nowrap" style="font-size: 16px;">
            Sistem Rapor Digital
        </span>
    </div>
</div>

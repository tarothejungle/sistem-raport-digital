@php
    $user = auth()->user();

    $tahunAjaran = \App\Models\TahunAjaran::query()
        ->where('is_active', true)
        ->first();

    $roleLabel = match ($user?->role) {
        'admin' => 'Administrator',
        'guru' => 'Guru',
        'siswa' => 'Siswa',
        default => 'Pengguna',
    };
@endphp

@if ($user)
    <div
        class="raport-sidebar-context"
        x-cloak
        x-show="$store.sidebar.isOpen"
        x-transition.opacity.duration.150ms
    >
        <div class="raport-sidebar-context__row">
            <span class="raport-sidebar-context__icon">
                <x-filament::icon icon="heroicon-o-user-circle" class="h-5 w-5" />
            </span>

            <span class="raport-sidebar-context__text">
                <span class="raport-sidebar-context__label">Masuk sebagai</span>
                <span class="raport-sidebar-context__value">{{ $roleLabel }}</span>
            </span>
        </div>

        <div class="raport-sidebar-context__row">
            <span class="raport-sidebar-context__icon">
                <x-filament::icon icon="heroicon-o-calendar-days" class="h-5 w-5" />
            </span>

            <span class="raport-sidebar-context__text">
                <span class="raport-sidebar-context__label">Tahun aktif</span>
                <span class="raport-sidebar-context__value">
                    {{ $tahunAjaran?->label ?? 'Belum diatur' }}
                </span>
            </span>
        </div>
    </div>
@endif

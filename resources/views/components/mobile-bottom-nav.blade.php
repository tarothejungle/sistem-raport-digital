@auth
@php
    $inputJadwals = \App\Filament\Admin\Pages\InputNilaiPerMapel::navigasiMataPelajaran();
    $inputJadwal = $inputJadwals->first();
    $inputNilaiUrl = $inputJadwal === null
        ? null
        : \App\Filament\Admin\Pages\InputNilaiPerMapel::getUrl(['jadwalMengajar' => $inputJadwal]);
    $inputNilaiSchedules = $inputJadwals->map(static function (\App\Models\JadwalMengajar $jadwal): array {
        $url = \App\Filament\Admin\Pages\InputNilaiPerMapel::getUrl(['jadwalMengajar' => $jadwal]);

        return [
            'key' => (string) $jadwal->getKey(),
            'label' => sprintf(
                '%s - %s',
                $jadwal->mataPelajaran?->nama_mapel ?? 'Mata Pelajaran',
                $jadwal->kelas?->nama_kelas ?? 'Kelas',
            ),
            'icon' => 'heroicon-o-book-open',
            'actions' => [
                ['label' => 'List Nilai Siswa', 'icon' => 'heroicon-o-list-bullet', 'url' => $url],
                ['label' => 'Tambah Nilai', 'icon' => 'heroicon-o-plus-circle', 'url' => $url.'?tableAction=tambahNilai'],
            ],
        ];
    })->values();
    $isAbsenSiswaPage = request()->routeIs(\App\Filament\Admin\Pages\AbsenSiswa::getRouteName());
    $isSiswaPage = request()->routeIs('filament.admin.resources.siswa.index');

    $masterItems = collect([
        [
            'key' => 'guru',
            'label' => 'Data Guru',
            'icon' => 'heroicon-o-user-group',
            'url' => \App\Filament\Admin\Resources\GuruResource::canViewAny() ? \App\Filament\Admin\Resources\GuruResource::getUrl('index') : null,
            'actions' => [
                ['label' => 'List Data Guru', 'icon' => 'heroicon-o-list-bullet', 'url' => \App\Filament\Admin\Resources\GuruResource::getUrl('index')],
                ['label' => 'Sync Guru', 'icon' => 'heroicon-o-arrow-path', 'url' => \App\Filament\Admin\Resources\GuruResource::getUrl('index', ['action' => 'tarikDataAbsensi'])],
                ['label' => 'Tambah Guru', 'icon' => 'heroicon-o-plus-circle', 'url' => \App\Filament\Admin\Resources\GuruResource::canCreate() ? \App\Filament\Admin\Resources\GuruResource::getUrl('create') : null],
            ],
        ],
        [
            'key' => 'siswa',
            'label' => 'Data Siswa',
            'icon' => 'heroicon-o-users',
            'url' => \App\Filament\Admin\Resources\SiswaResource::canViewAny() ? \App\Filament\Admin\Resources\SiswaResource::getUrl('index') : null,
            'actions' => [
                ['label' => 'List Data Siswa', 'icon' => 'heroicon-o-list-bullet', 'url' => \App\Filament\Admin\Resources\SiswaResource::getUrl('index')],
                ['label' => 'Tambah Siswa', 'icon' => 'heroicon-o-plus-circle', 'url' => \App\Filament\Admin\Resources\SiswaResource::canCreate() ? \App\Filament\Admin\Resources\SiswaResource::getUrl('create') : null],
                ['label' => 'Template Excel', 'icon' => 'heroicon-o-arrow-down-tray', 'url' => url('/templates/template-import-siswa.xlsx'), 'external' => true],
                ['label' => 'Import Excel', 'icon' => 'heroicon-o-arrow-up-tray', 'url' => \App\Filament\Admin\Resources\SiswaResource::getUrl('index', ['action' => 'importExcel'])],
                [
                    'label' => 'Import Siswa (.json)',
                    'icon' => 'heroicon-o-code-bracket-square',
                    'url' => \App\Filament\Admin\Resources\SiswaResource::getUrl('index', ['action' => 'importJson']),
                    'trigger' => $isSiswaPage ? 'raport-import-siswa-json-action' : null,
                ],
            ],
        ],
        [
            'key' => 'kelas',
            'label' => 'Data Kelas',
            'icon' => 'heroicon-o-building-library',
            'url' => \App\Filament\Admin\Resources\KelasResource::canViewAny() ? \App\Filament\Admin\Resources\KelasResource::getUrl('index') : null,
            'actions' => [
                ['label' => 'List Data Kelas', 'icon' => 'heroicon-o-list-bullet', 'url' => \App\Filament\Admin\Resources\KelasResource::getUrl('index')],
                ['label' => 'Tambah Semua Kelas', 'icon' => 'heroicon-o-plus-circle', 'url' => \App\Filament\Admin\Resources\KelasResource::canCreate() ? \App\Filament\Admin\Resources\KelasResource::getUrl('create') : null],
            ],
        ],
        [
            'key' => 'alumni',
            'label' => 'Data Alumni',
            'icon' => 'heroicon-o-academic-cap',
            'url' => \App\Filament\Admin\Resources\AlumniResource::canViewAny() ? \App\Filament\Admin\Resources\AlumniResource::getUrl('index') : null,
            'direct' => true,
            'actions' => [],
        ],
        [
            'key' => 'mapel',
            'label' => 'Mata Pelajaran',
            'icon' => 'heroicon-o-book-open',
            'url' => \App\Filament\Admin\Resources\MataPelajaranResource::canViewAny() ? \App\Filament\Admin\Resources\MataPelajaranResource::getUrl('index') : null,
            'actions' => [
                ['label' => 'List Mata Pelajaran', 'icon' => 'heroicon-o-list-bullet', 'url' => \App\Filament\Admin\Resources\MataPelajaranResource::getUrl('index')],
                ['label' => 'Tambah Mata Pelajaran', 'icon' => 'heroicon-o-plus-circle', 'url' => \App\Filament\Admin\Resources\MataPelajaranResource::canCreate() ? \App\Filament\Admin\Resources\MataPelajaranResource::getUrl('create') : null],
            ],
        ],
        [
            'key' => 'tahun',
            'label' => 'Tahun Ajaran',
            'icon' => 'heroicon-o-calendar-days',
            'url' => \App\Filament\Admin\Resources\TahunAjaranResource::canViewAny() ? \App\Filament\Admin\Resources\TahunAjaranResource::getUrl('index') : null,
            'actions' => [
                ['label' => 'List Tahun Ajaran', 'icon' => 'heroicon-o-list-bullet', 'url' => \App\Filament\Admin\Resources\TahunAjaranResource::getUrl('index')],
                ['label' => 'Set Tahun Ajaran', 'icon' => 'heroicon-o-plus-circle', 'url' => \App\Filament\Admin\Resources\TahunAjaranResource::canCreate() ? \App\Filament\Admin\Resources\TahunAjaranResource::getUrl('create') : null],
            ],
        ],
    ])->whereNotNull('url')->map(function (array $item): array {
        $item['actions'] = collect($item['actions'])
            ->filter(static fn (array $action): bool => filled($action['url'] ?? null) || filled($action['drawer'] ?? null))
            ->values()
            ->all();

        return $item;
    })->values();

    $academicItems = collect([
        [
            'label' => 'Pengajar Kelas',
            'icon' => 'heroicon-o-presentation-chart-bar',
            'url' => \App\Filament\Admin\Resources\JadwalMengajarResource::canViewAny() ? \App\Filament\Admin\Resources\JadwalMengajarResource::getUrl('index') : null,
            'actions' => [
                ['label' => 'List Pengajar Kelas', 'icon' => 'heroicon-o-list-bullet', 'url' => \App\Filament\Admin\Resources\JadwalMengajarResource::getUrl('index')],
                ['label' => 'Atur Pengajar Kelas', 'icon' => 'heroicon-o-plus-circle', 'url' => \App\Filament\Admin\Resources\JadwalMengajarResource::canCreate() ? \App\Filament\Admin\Resources\JadwalMengajarResource::getUrl('create') : null],
            ],
        ],
        [
            'label' => 'Kenaikan Kelas',
            'icon' => 'heroicon-o-arrow-trending-up',
            'url' => \App\Filament\Admin\Pages\KenaikanKelas::canAccess() ? \App\Filament\Admin\Pages\KenaikanKelas::getUrl() : null,
            'direct' => true,
            'actions' => [],
        ],
        [
            'label' => 'Input Nilai',
            'icon' => 'heroicon-o-clipboard-document-check',
            'url' => $inputNilaiUrl,
            'actions' => $inputNilaiUrl === null ? [] : [
                ['label' => 'Tambah Nilai', 'icon' => 'heroicon-o-plus-circle', 'drawer' => 'input-subjects'],
                ['label' => 'Template Nilai Siswa', 'icon' => 'heroicon-o-arrow-down-tray', 'url' => route('admin.nilai.template', ['jadwalMengajar' => $inputJadwal]), 'external' => true],
                ['label' => 'Import Nilai Siswa', 'icon' => 'heroicon-o-arrow-up-tray', 'url' => $inputNilaiUrl.'?tableAction=importNilaiSiswa'],
            ],
        ],
        [
            'label' => 'Absen Siswa',
            'icon' => 'heroicon-o-clipboard-document-list',
            'url' => \App\Filament\Admin\Pages\AbsenSiswa::canAccess() ? \App\Filament\Admin\Pages\AbsenSiswa::getUrl() : null,
            'actions' => [
                ['label' => 'List Absen Siswa', 'icon' => 'heroicon-o-list-bullet', 'url' => \App\Filament\Admin\Pages\AbsenSiswa::getUrl()],
                [
                    'label' => 'Isi Absen Siswa',
                    'icon' => 'heroicon-o-pencil-square',
                    'url' => \App\Filament\Admin\Pages\AbsenSiswa::getUrl(),
                    'trigger' => $isAbsenSiswaPage ? 'raport-isi-absensi-action' : null,
                ],
            ],
        ],
        [
            'label' => 'Leger Nilai PTS',
            'icon' => 'heroicon-o-table-cells',
            'url' => \App\Filament\Admin\Pages\LegerNilaiPts::canAccess() ? \App\Filament\Admin\Pages\LegerNilaiPts::getUrl() : null,
            'direct' => true,
            'actions' => [],
        ],
        [
            'label' => 'Akses Nilai Siswa',
            'icon' => 'heroicon-o-key',
            'url' => \App\Filament\Admin\Resources\AksesNilaiSiswaResource::canViewAny() ? \App\Filament\Admin\Resources\AksesNilaiSiswaResource::getUrl('index') : null,
            'direct' => true,
            'actions' => [],
        ],
        [
            'label' => 'Cetak Rapor Siswa',
            'icon' => 'heroicon-o-printer',
            'url' => \App\Filament\Admin\Pages\CetakRaporSiswa::canAccess() ? \App\Filament\Admin\Pages\CetakRaporSiswa::getUrl() : null,
            'direct' => true,
            'actions' => [],
        ],
    ])->whereNotNull('url')->map(function (array $item): array {
        $item['actions'] = collect($item['actions'])
            ->filter(static fn (array $action): bool => filled($action['url'] ?? null) || filled($action['drawer'] ?? null))
            ->values()
            ->all();

        return $item;
    })->values();

    $canViewSettings = \App\Filament\Admin\Resources\PengaturanMadrasahResource::canViewAny();
    $settingsRecord = $canViewSettings
        ? \App\Models\PengaturanMadrasah::query()->first()
        : null;
    $settingsItems = $canViewSettings ? collect([
        [
            'label' => 'Pengaturan Madrasah',
            'icon' => 'heroicon-o-cog-6-tooth',
            'url' => \App\Filament\Admin\Resources\PengaturanMadrasahResource::getUrl('index'),
        ],
        [
            'label' => 'Isi Pengaturan Madrasah',
            'icon' => 'heroicon-o-pencil-square',
            'url' => $settingsRecord !== null
                ? \App\Filament\Admin\Resources\PengaturanMadrasahResource::getUrl('edit', ['record' => $settingsRecord])
                : (\App\Filament\Admin\Resources\PengaturanMadrasahResource::canCreate()
                    ? \App\Filament\Admin\Resources\PengaturanMadrasahResource::getUrl('create')
                : null),
        ],
        [
            'label' => 'Maintenance Website',
            'icon' => 'heroicon-o-wrench-screwdriver',
            'url' => \App\Filament\Admin\Pages\MaintenanceSettings::canAccess()
                ? \App\Filament\Admin\Pages\MaintenanceSettings::getUrl()
                : null,
        ],
        [
            'label' => 'Pengumuman Website',
            'icon' => 'heroicon-o-megaphone',
            'url' => \App\Filament\Admin\Pages\AnnouncementSettings::canAccess()
                ? \App\Filament\Admin\Pages\AnnouncementSettings::getUrl()
                : null,
        ],
    ])->whereNotNull('url')->values() : collect();
    $dashboardUrl = \App\Filament\Admin\Pages\Dashboard::canAccess()
        ? \App\Filament\Admin\Pages\Dashboard::getUrl()
        : (\App\Filament\Admin\Resources\NilaiSiswaResource::canViewAny() ? \App\Filament\Admin\Resources\NilaiSiswaResource::getUrl('index') : filament()->getUrl());
    $currentUrl = url()->current();
    $settingsActive = $settingsItems->contains(
        static fn (array $item): bool => str_starts_with($currentUrl, $item['url']),
    );
    $masterActive = $masterItems->contains(
        static fn (array $item): bool => str_starts_with($currentUrl, $item['url']),
    );
    $academicActive = $academicItems->contains(
        static fn (array $item): bool => str_starts_with($currentUrl, $item['url']),
    );
@endphp

<nav
    x-data="{
        open: false,
        activeTab: null,
        activeMasterMenu: null,
        activeAcademicMenu: null,
        inputNilaiStep: null,
        activeInputSchedule: null,
        activeInputScheduleLabel: null,
        visible: true,
        lastScrollY: window.scrollY,
        scrollHandler: null,
        modalObserver: null,
        init() {
            this.scrollHandler = () => {
                const currentScrollY = Math.max(window.scrollY, 0)
                const delta = currentScrollY - this.lastScrollY

                if (Math.abs(delta) < 8) return

                if (delta > 0 && currentScrollY > 80) {
                    this.visible = false
                    this.open = false
                    this.activeTab = null
                    this.activeMasterMenu = null
                    this.activeAcademicMenu = null
                    this.inputNilaiStep = null
                    this.activeInputSchedule = null
                    this.activeInputScheduleLabel = null
                } else if (delta < 0) {
                    this.visible = true
                }

                this.lastScrollY = currentScrollY
            }

            window.addEventListener('scroll', this.scrollHandler, { passive: true })
            this.modalObserver = new MutationObserver(() => this.syncInputNilaiModal())
            this.modalObserver.observe(document.body, { attributes: true, childList: true, subtree: true })
            this.syncInputNilaiModal()
        },
        destroy() {
            window.removeEventListener('scroll', this.scrollHandler)
            this.modalObserver?.disconnect()
            document.body.classList.remove('raport-input-nilai-modal-active')
            document.body.classList.remove('raport-siswa-json-modal-active')
        },
        syncInputNilaiModal() {
            const windowElement = document.querySelector('.raport-input-nilai-modal')
            const modal = windowElement?.closest('.fi-modal.fi-modal-open')
            const active = Boolean(modal)

            document.body.classList.toggle('raport-input-nilai-modal-active', active)

            const jsonImportWindow = document.querySelector('.raport-siswa-json-import-modal')
            const jsonImportModal = jsonImportWindow?.closest('.fi-modal.fi-modal-open')
            document.body.classList.toggle('raport-siswa-json-modal-active', Boolean(jsonImportModal))
        },
    }"
    x-on:keydown.escape.window="activeInputSchedule ? (activeInputSchedule = null, activeInputScheduleLabel = null) : (inputNilaiStep ? inputNilaiStep = null : (activeMasterMenu ? activeMasterMenu = null : (activeAcademicMenu ? activeAcademicMenu = null : (open = false, activeTab = null))))"
    x-on:livewire:navigating.window="open = false; activeTab = null; activeMasterMenu = null; activeAcademicMenu = null; inputNilaiStep = null; activeInputSchedule = null; activeInputScheduleLabel = null"
    x-on:click.outside="open = false; activeTab = null; activeMasterMenu = null; activeAcademicMenu = null; inputNilaiStep = null; activeInputSchedule = null; activeInputScheduleLabel = null"
    x-bind:class="{ 'is-scroll-hidden': ! visible }"
    class="raport-mobile-nav block md:hidden"
    aria-label="Navigasi utama mobile"
>
    <div
        x-cloak
        x-show="open && activeTab"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-3 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-3 scale-95"
        class="raport-mobile-nav__drawer"
    >
        <div class="raport-mobile-nav__drawer-header">
            <div>
                <span class="raport-mobile-nav__eyebrow" x-text="activeInputSchedule ? activeAcademicMenu : (inputNilaiStep ? 'Pilih Mata Pelajaran' : (activeMasterMenu ? 'Master Data' : (activeAcademicMenu ? 'Akademik' : 'Menu')))"></span>
                <h2 x-text="activeInputScheduleLabel ?? (inputNilaiStep ? 'Mapel yang Diampu' : (activeMasterMenu ?? activeAcademicMenu ?? (activeTab === 'settings' ? 'Pengaturan' : (activeTab === 'master' ? 'Master Data' : 'Akademik'))))"></h2>
            </div>

            <button
                type="button"
                x-on:click="open = false; activeTab = null; activeMasterMenu = null; activeAcademicMenu = null; inputNilaiStep = null; activeInputSchedule = null; activeInputScheduleLabel = null"
                class="raport-mobile-nav__close"
                aria-label="Tutup submenu"
            >
                <x-filament::icon icon="heroicon-o-x-mark" class="h-5 w-5" />
            </button>
        </div>

        <div x-show="activeTab === 'settings'" class="raport-mobile-nav__submenu">
            @forelse ($settingsItems as $item)
                <a href="{{ $item['url'] }}" wire:navigate class="raport-mobile-nav__submenu-link">
                    <span class="raport-mobile-nav__submenu-icon">
                        <x-filament::icon :icon="$item['icon']" class="h-5 w-5" />
                    </span>
                    <span>{{ $item['label'] }}</span>
                    <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4 opacity-50" />
                </a>
            @empty
                <p class="raport-mobile-nav__empty">Tidak ada menu Pengaturan yang dapat diakses.</p>
            @endforelse
        </div>

        <div x-show="activeTab === 'master' && ! activeMasterMenu" class="raport-mobile-nav__submenu">
            @forelse ($masterItems as $item)
                @if ($item['direct'] ?? false)
                    <a href="{{ $item['url'] }}" wire:navigate class="raport-mobile-nav__submenu-link">
                        <span class="raport-mobile-nav__submenu-icon">
                            <x-filament::icon :icon="$item['icon']" class="h-5 w-5" />
                        </span>
                        <span>{{ $item['label'] }}</span>
                        <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4 opacity-50" />
                    </a>
                @else
                    <button
                        type="button"
                        x-on:click="activeMasterMenu = @js($item['label'])"
                        class="raport-mobile-nav__submenu-link"
                        aria-label="Buka menu {{ $item['label'] }}"
                    >
                        <span class="raport-mobile-nav__submenu-icon">
                            <x-filament::icon :icon="$item['icon']" class="h-5 w-5" />
                        </span>
                        <span>{{ $item['label'] }}</span>
                        <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4 opacity-50" />
                    </button>
                @endif
            @empty
                <p class="raport-mobile-nav__empty">Tidak ada menu Master Data yang dapat diakses.</p>
            @endforelse
        </div>

        @foreach ($masterItems as $item)
            @unless ($item['direct'] ?? false)
            <div x-cloak x-show="activeTab === 'master' && activeMasterMenu === @js($item['label'])" class="raport-mobile-nav__submenu">
                <button
                    type="button"
                    x-on:click="activeMasterMenu = null"
                    class="raport-mobile-nav__submenu-link raport-mobile-nav__submenu-back"
                >
                    <span class="raport-mobile-nav__submenu-icon">
                        <x-filament::icon icon="heroicon-o-arrow-left" class="h-5 w-5" />
                    </span>
                    <span>Kembali ke Master Data</span>
                </button>

                @foreach ($item['actions'] as $action)
                    @if (filled($action['trigger'] ?? null))
                        <button
                            type="button"
                            x-on:click="
                                const action = document.getElementById(@js($action['trigger']));
                                if (! action || action.disabled) return;
                                action.click();
                                open = false;
                                activeTab = null;
                                activeMasterMenu = null;
                            "
                            class="raport-mobile-nav__submenu-link"
                        >
                            <span class="raport-mobile-nav__submenu-icon">
                                <x-filament::icon :icon="$action['icon']" class="h-5 w-5" />
                            </span>
                            <span>{{ $action['label'] }}</span>
                            <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4 opacity-50" />
                        </button>
                    @else
                        <a
                            href="{{ $action['url'] }}"
                            @if (! ($action['external'] ?? false)) wire:navigate @else target="_blank" rel="noopener noreferrer" @endif
                            class="raport-mobile-nav__submenu-link"
                        >
                            <span class="raport-mobile-nav__submenu-icon">
                                <x-filament::icon :icon="$action['icon']" class="h-5 w-5" />
                            </span>
                            <span>{{ $action['label'] }}</span>
                            <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4 opacity-50" />
                        </a>
                    @endif
                @endforeach
            </div>
            @endunless
        @endforeach

        <div x-show="activeTab === 'academic' && ! activeAcademicMenu && ! inputNilaiStep" class="raport-mobile-nav__submenu">
            @forelse ($academicItems as $item)
                @if ($item['direct'] ?? false)
                    <a href="{{ $item['url'] }}" wire:navigate class="raport-mobile-nav__submenu-link">
                        <span class="raport-mobile-nav__submenu-icon">
                            <x-filament::icon :icon="$item['icon']" class="h-5 w-5" />
                        </span>
                        <span>{{ $item['label'] }}</span>
                        <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4 opacity-50" />
                    </a>
                @else
                    <button
                        type="button"
                        x-on:click="activeAcademicMenu = @js($item['label'])"
                        class="raport-mobile-nav__submenu-link"
                        aria-label="Buka menu {{ $item['label'] }}"
                    >
                        <span class="raport-mobile-nav__submenu-icon">
                            <x-filament::icon :icon="$item['icon']" class="h-5 w-5" />
                        </span>
                        <span>{{ $item['label'] }}</span>
                        <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4 opacity-50" />
                    </button>
                @endif
            @empty
                <p class="raport-mobile-nav__empty">Tidak ada menu Akademik yang dapat diakses.</p>
            @endforelse
        </div>

        @foreach ($academicItems as $item)
            @unless ($item['direct'] ?? false)
            <div x-cloak x-show="activeTab === 'academic' && activeAcademicMenu === @js($item['label']) && ! inputNilaiStep" class="raport-mobile-nav__submenu">
                <button
                    type="button"
                    x-on:click="activeAcademicMenu = null"
                    class="raport-mobile-nav__submenu-link raport-mobile-nav__submenu-back"
                >
                    <span class="raport-mobile-nav__submenu-icon">
                        <x-filament::icon icon="heroicon-o-arrow-left" class="h-5 w-5" />
                    </span>
                    <span>Kembali ke Akademik</span>
                </button>

                @foreach ($item['actions'] as $action)
                    @if (filled($action['trigger'] ?? null))
                        <button
                            type="button"
                            x-on:click="
                                const action = document.getElementById(@js($action['trigger']));
                                if (! action || action.disabled) return;
                                action.click();
                                open = false;
                                activeTab = null;
                                activeAcademicMenu = null;
                            "
                            class="raport-mobile-nav__submenu-link"
                        >
                            <span class="raport-mobile-nav__submenu-icon">
                                <x-filament::icon :icon="$action['icon']" class="h-5 w-5" />
                            </span>
                            <span>{{ $action['label'] }}</span>
                            <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4 opacity-50" />
                        </button>
                    @elseif (($action['drawer'] ?? null) === 'input-subjects')
                        <button
                            type="button"
                            x-on:click="inputNilaiStep = 'subjects'"
                            class="raport-mobile-nav__submenu-link"
                        >
                            <span class="raport-mobile-nav__submenu-icon">
                                <x-filament::icon :icon="$action['icon']" class="h-5 w-5" />
                            </span>
                            <span>{{ $action['label'] }}</span>
                            <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4 opacity-50" />
                        </button>
                    @else
                        <a
                            href="{{ $action['url'] }}"
                            @if (! ($action['external'] ?? false)) wire:navigate @else target="_blank" rel="noopener noreferrer" @endif
                            class="raport-mobile-nav__submenu-link"
                        >
                            <span class="raport-mobile-nav__submenu-icon">
                                <x-filament::icon :icon="$action['icon']" class="h-5 w-5" />
                            </span>
                            <span>{{ $action['label'] }}</span>
                            <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4 opacity-50" />
                        </a>
                    @endif
                @endforeach
            </div>
            @endunless
        @endforeach

        @if ($inputNilaiSchedules->isNotEmpty())
        <div x-cloak x-show="activeTab === 'academic' && inputNilaiStep === 'subjects' && ! activeInputSchedule" class="raport-mobile-nav__submenu">
            <button
                type="button"
                x-on:click="inputNilaiStep = null"
                class="raport-mobile-nav__submenu-link raport-mobile-nav__submenu-back"
            >
                <span class="raport-mobile-nav__submenu-icon">
                    <x-filament::icon icon="heroicon-o-arrow-left" class="h-5 w-5" />
                </span>
                <span>Kembali ke Input Nilai</span>
            </button>

            @foreach ($inputNilaiSchedules as $schedule)
                <button
                    type="button"
                    x-on:click="activeInputSchedule = @js($schedule['key']); activeInputScheduleLabel = @js($schedule['label'])"
                    class="raport-mobile-nav__submenu-link"
                    aria-label="Buka nilai {{ $schedule['label'] }}"
                >
                    <span class="raport-mobile-nav__submenu-icon">
                        <x-filament::icon :icon="$schedule['icon']" class="h-5 w-5" />
                    </span>
                    <span>{{ $schedule['label'] }}</span>
                    <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4 opacity-50" />
                </button>
            @endforeach
        </div>

        @foreach ($inputNilaiSchedules as $schedule)
            <div x-cloak x-show="activeTab === 'academic' && inputNilaiStep === 'subjects' && activeInputSchedule === @js($schedule['key'])" class="raport-mobile-nav__submenu">
                <button
                    type="button"
                    x-on:click="activeInputSchedule = null; activeInputScheduleLabel = null"
                    class="raport-mobile-nav__submenu-link raport-mobile-nav__submenu-back"
                >
                    <span class="raport-mobile-nav__submenu-icon">
                        <x-filament::icon icon="heroicon-o-arrow-left" class="h-5 w-5" />
                    </span>
                    <span>Kembali ke Daftar Mapel</span>
                </button>

                @foreach ($schedule['actions'] as $action)
                    <a href="{{ $action['url'] }}" wire:navigate class="raport-mobile-nav__submenu-link">
                        <span class="raport-mobile-nav__submenu-icon">
                            <x-filament::icon :icon="$action['icon']" class="h-5 w-5" />
                        </span>
                        <span>{{ $action['label'] }}</span>
                        <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4 opacity-50" />
                    </a>
                @endforeach
            </div>
        @endforeach
        @endif
    </div>

    <div class="raport-mobile-nav__bar">
        <a
            href="{{ $dashboardUrl }}"
            wire:navigate
            @class([
                'raport-mobile-nav__item',
                'is-active' => $currentUrl === $dashboardUrl,
            ])
            aria-label="Dashboard"
        >
            <x-filament::icon icon="heroicon-o-squares-2x2" class="h-5 w-5" />
            <span>Dashboard</span>
        </a>

        @if ($settingsItems->isNotEmpty())
            <button
                type="button"
                x-on:click="activeTab === 'settings' && open ? (open = false, activeTab = null) : (activeTab = 'settings', activeMasterMenu = null, activeAcademicMenu = null, inputNilaiStep = null, activeInputSchedule = null, activeInputScheduleLabel = null, open = true)"
                x-bind:aria-expanded="open && activeTab === 'settings'"
                @class([
                    'raport-mobile-nav__item',
                    'is-active' => $settingsActive,
                ])
                aria-label="Buka menu Pengaturan"
            >
                <x-filament::icon icon="heroicon-o-cog-6-tooth" class="h-5 w-5" />
                <span>Pengaturan</span>
            </button>
        @endif

        @if ($masterItems->isNotEmpty())
            <button
                type="button"
                x-on:click="activeTab === 'master' && open ? (open = false, activeTab = null, activeMasterMenu = null) : (activeTab = 'master', activeMasterMenu = null, activeAcademicMenu = null, inputNilaiStep = null, activeInputSchedule = null, activeInputScheduleLabel = null, open = true)"
                x-bind:aria-expanded="open && activeTab === 'master'"
                @class(['raport-mobile-nav__item', 'is-active' => $masterActive])
                aria-label="Buka menu Master Data"
            >
                <x-filament::icon icon="heroicon-o-circle-stack" class="h-5 w-5" />
                <span>Master Data</span>
            </button>
        @endif

        @if ($academicItems->isNotEmpty())
            <button
                type="button"
                x-on:click="activeTab === 'academic' && open ? (open = false, activeTab = null, activeAcademicMenu = null, inputNilaiStep = null, activeInputSchedule = null, activeInputScheduleLabel = null) : (activeTab = 'academic', activeMasterMenu = null, activeAcademicMenu = null, inputNilaiStep = null, activeInputSchedule = null, activeInputScheduleLabel = null, open = true)"
                x-bind:aria-expanded="open && activeTab === 'academic'"
                @class(['raport-mobile-nav__item', 'is-active' => $academicActive])
                aria-label="Buka menu Akademik"
            >
                <x-filament::icon icon="heroicon-o-academic-cap" class="h-5 w-5" />
                <span>Akademik</span>
            </button>
        @endif

    </div>
</nav>
@endauth

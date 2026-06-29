@php
    $pageClass = \App\Filament\Admin\Pages\InputNilaiPerMapel::class;

    $jadwalMengajars = $pageClass::navigasiMataPelajaran();

    $routeJadwal = request()->route('jadwalMengajar');

    $activeJadwalId = $routeJadwal instanceof \App\Models\JadwalMengajar
        ? $routeJadwal->getKey()
        : $routeJadwal;

    $isAdmin = auth()->user()?->isAdmin() ?? false;
@endphp

@if ($jadwalMengajars->isNotEmpty())
    <li
        id="raport-input-nilai-navigation"
        class="fi-sidebar-item hidden"
        x-show="$store.sidebar.isOpen"
    >
        <details
            class="group"
            @if (request()->routeIs($pageClass::getRouteName())) open @endif
        >
            <summary
                class="fi-sidebar-item-button relative flex cursor-pointer list-none items-center gap-x-3 rounded-lg px-2 py-2"
            >
                <x-filament::icon
                    icon="heroicon-o-clipboard-document-check"
                    class="fi-sidebar-item-icon h-6 w-6 text-gray-400 dark:text-gray-500"
                />

                <span
                    class="fi-sidebar-item-label flex-1 truncate text-sm font-medium text-gray-700 dark:text-gray-200"
                >
                    Input Nilai
                </span>

                <x-filament::icon
                    icon="heroicon-m-chevron-right"
                    class="h-4 w-4 text-gray-400 transition-transform duration-200 group-open:rotate-90 dark:text-gray-500"
                />
            </summary>

            <div
                class="ms-5 mt-1 flex flex-col gap-y-1 border-s border-gray-300 ps-3 dark:border-gray-600"
            >
                @foreach ($jadwalMengajars->groupBy('mapel_id') as $jadwalPerMapel)
                    @php
                        $jadwalPertama = $jadwalPerMapel->first();

                        $mapelAktif = $jadwalPerMapel->contains(
                            static fn (\App\Models\JadwalMengajar $jadwalMengajar): bool =>
                                (string) $jadwalMengajar->getKey() === (string) $activeJadwalId,
                        );
                    @endphp

                    <details @if ($mapelAktif) open @endif>
                        <summary
                            class="flex cursor-pointer list-none items-center gap-x-2 rounded-md px-2 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-white/5"
                        >
                            <x-filament::icon
                                icon="heroicon-o-book-open"
                                class="h-4 w-4 text-gray-400 dark:text-gray-500"
                            />

                            <span class="flex-1 truncate">
                                {{ $jadwalPertama->mataPelajaran?->nama_mapel ?? 'Mata Pelajaran' }}
                            </span>

                            <x-filament::icon
                                icon="heroicon-m-chevron-right"
                                class="h-3.5 w-3.5 text-gray-400 transition-transform duration-200 group-open:rotate-90 dark:text-gray-500"
                            />
                        </summary>

                        <div class="ms-5 mt-1 flex flex-col gap-y-1">
                            @foreach ($jadwalPerMapel as $jadwalMengajar)
                                @php
                                    $kelasAktif = (string) $jadwalMengajar->getKey() === (string) $activeJadwalId;

                                    $labelKelas = $jadwalMengajar->kelas?->nama_kelas ?? 'Kelas';

                                    if ($isAdmin) {
                                        $labelKelas .= ' — '.($jadwalMengajar->guru?->nama ?? 'Guru');
                                    }
                                @endphp

                                <a
                                    href="{{ $pageClass::getUrl(['jadwalMengajar' => $jadwalMengajar]) }}"
                                    @class([
                                        'flex items-center gap-x-2 rounded-md px-2 py-2 text-sm transition',
                                        'bg-primary-50 font-semibold text-primary-600 dark:bg-primary-500/10 dark:text-primary-400' => $kelasAktif,
                                        'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5' => ! $kelasAktif,
                                    ])
                                >
                                    <span class="flex-1 truncate">
                                        {{ $labelKelas }}
                                    </span>

                                    @if ($kelasAktif)
                                        <x-filament::icon
                                            icon="heroicon-m-check-circle"
                                            class="h-4 w-4"
                                        />
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endforeach
            </div>
        </details>
    </li>

    @once
        <script>
            (() => {
                const placeInputNilaiNavigation = () => {
                    const navigation = document.getElementById(
                        'raport-input-nilai-navigation',
                    );

                    if (!navigation) {
                        return;
                    }

                    const akademikItems = document.querySelector(
                        '.fi-sidebar-group[data-group-label="Akademik"] .fi-sidebar-group-items',
                    );

                    if (!akademikItems) {
                        return;
                    }

                    const accessNilaiItem = [...akademikItems.children].find(
                        (item) => item.textContent.trim().startsWith('Akses Nilai Siswa'),
                    );

                    if (navigation.parentElement !== akademikItems) {
                        if (accessNilaiItem) {
                            akademikItems.insertBefore(
                                navigation,
                                accessNilaiItem,
                            );
                        } else {
                            akademikItems.appendChild(navigation);
                        }
                    }

                    navigation.classList.remove('hidden');
                };

                document.addEventListener(
                    'DOMContentLoaded',
                    placeInputNilaiNavigation,
                );

                document.addEventListener(
                    'livewire:navigated',
                    placeInputNilaiNavigation,
                );

                setTimeout(placeInputNilaiNavigation, 250);
            })();
        </script>
    @endonce
@endif
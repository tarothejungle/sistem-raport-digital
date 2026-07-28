<x-filament-panels::page>
    <div class="raport-page-stack raport-input-nilai-page">
        @php
            $kkm = app(\App\Services\NilaiBatchService::class)
                ->defaultKkm($this->jadwalMengajar);
        @endphp

        <section class="raport-flow-panel">
            <div>
                <span class="raport-eyebrow">Input Nilai</span>
                <h2 class="raport-flow-panel__title">
                    Lengkapi nilai siswa per mata pelajaran dan kelas.
                </h2>
            </div>

            <div class="raport-flow-steps">
                <span>
                    <x-filament::icon icon="heroicon-o-book-open" class="h-4 w-4" />
                    Pilih mapel
                </span>
                <span>
                    <x-filament::icon icon="heroicon-o-pencil-square" class="h-4 w-4" />
                    Isi nilai
                </span>
                <span>
                    <x-filament::icon icon="heroicon-o-check-circle" class="h-4 w-4" />
                    Simpan
                </span>
            </div>
        </section>

        <div class="raport-context-grid">
            <div class="raport-context-card">
                <span class="raport-context-card__icon">
                    <x-filament::icon icon="heroicon-o-book-open" class="h-5 w-5" />
                </span>
                <div>
                    <div class="raport-context-card__label">Mata Pelajaran</div>
                    <div class="raport-context-card__value">
                        {{ $this->jadwalMengajar->mataPelajaran?->nama_mapel ?? '-' }}
                    </div>
                </div>
            </div>

            <div class="raport-context-card">
                <span class="raport-context-card__icon">
                    <x-filament::icon icon="heroicon-o-building-library" class="h-5 w-5" />
                </span>
                <div>
                    <div class="raport-context-card__label">Kelas</div>
                    <div class="raport-context-card__value">
                        {{ $this->jadwalMengajar->kelas?->nama_kelas ?? '-' }}
                    </div>
                </div>
            </div>

            <div class="raport-context-card">
                <span class="raport-context-card__icon">
                    <x-filament::icon icon="heroicon-o-user-circle" class="h-5 w-5" />
                </span>
                <div>
                    <div class="raport-context-card__label">Guru</div>
                    <div class="raport-context-card__value">
                        {{ $this->jadwalMengajar->guru?->nama ?? '-' }}
                    </div>
                </div>
            </div>

            <div class="raport-context-card">
                <span class="raport-context-card__icon">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="h-5 w-5" />
                </span>
                <div>
                    <div class="raport-context-card__label">Tahun Ajaran / KKM</div>
                    <div class="raport-context-card__value">
                        {{ $this->jadwalMengajar->tahunAjaran?->label ?? '-' }} / {{ $kkm }}
                    </div>
                </div>
            </div>
        </div>

        {{ $this->table }}
    </div>

    @once
        <script>
            (() => {
                if (window.__raportInputNilaiFocusModeInitialized) {
                    window.__syncRaportInputNilaiFocusMode?.();

                    return;
                }

                window.__raportInputNilaiFocusModeInitialized = true;

                const focusClass = 'raport-input-nilai-focus';
                const modalSelector = '.raport-input-nilai-modal';

                const isVisible = (element) => element instanceof HTMLElement
                    && element.getClientRects().length > 0
                    && window.getComputedStyle(element).display !== 'none';

                const syncFocusMode = () => {
                    const isModalOpen = [...document.querySelectorAll(modalSelector)]
                        .some(isVisible);

                    document.documentElement.classList.toggle(
                        focusClass,
                        isModalOpen,
                    );
                };

                const scheduleSync = () => window.requestAnimationFrame(syncFocusMode);

                window.__syncRaportInputNilaiFocusMode = scheduleSync;

                new MutationObserver(scheduleSync).observe(document.body, {
                    attributes: true,
                    attributeFilter: ['class', 'style'],
                    childList: true,
                    subtree: true,
                });

                document.addEventListener('DOMContentLoaded', scheduleSync);
                document.addEventListener('livewire:navigated', scheduleSync);
                scheduleSync();
            })();
        </script>
    @endonce
</x-filament-panels::page>

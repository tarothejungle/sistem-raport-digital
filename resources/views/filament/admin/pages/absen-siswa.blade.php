<x-filament-panels::page>
    <div class="raport-page-stack raport-absen-siswa-page">
        {{ $this->form }}
        {{ $this->table }}
    </div>

    @once
        <script>
            (() => {
                if (window.__raportAbsenSiswaFocusModeInitialized) {
                    window.__syncRaportAbsenSiswaFocusMode?.();

                    return;
                }

                window.__raportAbsenSiswaFocusModeInitialized = true;

                const focusClass = 'raport-absen-siswa-focus';
                const modalSelector = '.raport-absen-siswa-modal';

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

                window.__syncRaportAbsenSiswaFocusMode = scheduleSync;

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

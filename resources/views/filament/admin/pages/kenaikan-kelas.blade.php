<x-filament-panels::page>
    <div class="raport-page-stack raport-kenaikan-kelas-page">
        <section class="raport-flow-panel">
            <div>
                <span class="raport-eyebrow">Kenaikan Kelas</span>
                <h2 class="raport-flow-panel__title">Proses naik kelas dan kelulusan tanpa menghilangkan jejak tahun ajaran lama.</h2>
            </div>

            <div class="raport-flow-steps">
                <span>
                    <x-filament::icon icon="heroicon-o-calendar-days" class="h-4 w-4" />
                    Pilih periode
                </span>
                <span>
                    <x-filament::icon icon="heroicon-o-check-circle" class="h-4 w-4" />
                    Centang siswa
                </span>
                <span>
                    <x-filament::icon icon="heroicon-o-arrow-trending-up" class="h-4 w-4" />
                    Naikkan atau luluskan
                </span>
            </div>
        </section>

        {{ $this->form }}

        {{ $this->table }}
    </div>
</x-filament-panels::page>

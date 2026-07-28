<x-filament-panels::page>
    <div class="raport-page-stack raport-cetak-rapor-page">
        <section class="raport-flow-panel">
            <div>
                <span class="raport-eyebrow">Cetak Rapor</span>
                <h2 class="raport-flow-panel__title">Siapkan rapor siswa berdasarkan periode aktif.</h2>
            </div>

            <div class="raport-flow-steps">
                <span>
                    <x-filament::icon icon="heroicon-o-calendar-days" class="h-4 w-4" />
                    Pilih periode
                </span>
                <span>
                    <x-filament::icon icon="heroicon-o-clipboard-document-check" class="h-4 w-4" />
                    Cek nilai final
                </span>
                <span>
                    <x-filament::icon icon="heroicon-o-printer" class="h-4 w-4" />
                    Pratinjau atau unduh
                </span>
            </div>
        </section>

        {{ $this->form }}

        {{ $this->table }}
    </div>
</x-filament-panels::page>

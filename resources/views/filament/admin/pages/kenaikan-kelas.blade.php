<x-filament-panels::page>
    <div class="raport-page-stack raport-kenaikan-kelas-page">
        <section class="raport-flow-panel">
            <div class="raport-flow-panel__content">
                <span class="raport-eyebrow">Kenaikan Kelas</span>
                <h2 class="raport-flow-panel__title">Proses naik kelas dan kelulusan tanpa menghilangkan jejak tahun ajaran lama.</h2>
            </div>

            <div class="raport-flow-steps">
                <span>
                    <x-filament::icon icon="heroicon-o-calendar-days" class="h-4 w-4 shrink-0" />
                    Pilih periode
                </span>
                <span>
                    <x-filament::icon icon="heroicon-o-check-circle" class="h-4 w-4 shrink-0" />
                    Centang siswa
                </span>
                <span>
                    <x-filament::icon icon="heroicon-o-arrow-trending-up" class="h-4 w-4 shrink-0" />
                    Naikkan atau luluskan
                </span>
            </div>
        </section>

        {{ $this->form }}

        {{ $this->table }}
    </div>

    <style>
        @media (max-width: 768px) {
            .raport-kenaikan-kelas-page .raport-flow-panel {
                display: flex !important;
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 0.85rem !important;
                padding: 1rem 1.1rem !important;
            }

            .raport-kenaikan-kelas-page .raport-flow-panel__content {
                width: 100% !important;
            }

            .raport-kenaikan-kelas-page .raport-flow-panel__title {
                font-size: 1.1rem !important;
                line-height: 1.35 !important;
                overflow-wrap: break-word !important;
                word-break: break-word !important;
            }

            .raport-kenaikan-kelas-page .raport-flow-steps {
                display: flex !important;
                flex-wrap: wrap !important;
                justify-content: flex-start !important;
                align-items: center !important;
                gap: 0.45rem !important;
                width: 100% !important;
            }

            .raport-kenaikan-kelas-page .raport-flow-steps span {
                font-size: 0.74rem !important;
                padding: 0.35rem 0.6rem !important;
                white-space: nowrap !important;
            }
        }

        @media (max-width: 480px) {
            .raport-kenaikan-kelas-page .raport-flow-panel {
                padding: 0.85rem 0.9rem !important;
                gap: 0.75rem !important;
            }

            .raport-kenaikan-kelas-page .raport-flow-panel__title {
                font-size: 1.025rem !important;
                line-height: 1.35 !important;
            }

            .raport-kenaikan-kelas-page .raport-eyebrow {
                font-size: 0.68rem !important;
                margin-bottom: 0.2rem !important;
            }

            .raport-kenaikan-kelas-page .raport-flow-steps {
                gap: 0.35rem !important;
            }

            .raport-kenaikan-kelas-page .raport-flow-steps span {
                font-size: 0.7rem !important;
                padding: 0.3rem 0.55rem !important;
            }
        }
    </style>
</x-filament-panels::page>

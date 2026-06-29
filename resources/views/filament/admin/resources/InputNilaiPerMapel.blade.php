<x-filament-panels::page>
    <div class="raport-input-nilai-page">
        <style>
            @media (min-width: 640px) {
                .raport-input-nilai-page .fi-ta-header-ctn {
                    display: grid;
                    grid-template-columns: minmax(0, 1fr) auto;
                    align-items: center;
                    border-bottom: 1px solid rgb(229 231 235);
                }

                .dark .raport-input-nilai-page .fi-ta-header-ctn {
                    border-color: rgb(55 65 81);
                }

                .raport-input-nilai-page .fi-ta-header {
                    grid-column: 2;
                    grid-row: 1;
                    padding: 1rem 1.5rem;
                    border-bottom: 0;
                }

                .raport-input-nilai-page .fi-ta-header-toolbar {
                    grid-column: 1;
                    grid-row: 1;
                    min-height: 0;
                    padding: 1rem 0.3rem;
                    border-bottom: 0;
                    justify-content: flex-start;
                }

                .raport-input-nilai-page .fi-ta-header-toolbar > .ms-auto {
                    margin-left: 0;
                }
            }
        </style>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
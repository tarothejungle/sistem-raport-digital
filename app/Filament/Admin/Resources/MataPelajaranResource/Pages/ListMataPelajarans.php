<?php

namespace App\Filament\Admin\Resources\MataPelajaranResource\Pages;

use App\Filament\Admin\Resources\MataPelajaranResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMataPelajarans extends ListRecords
{
    protected static string $resource = MataPelajaranResource::class;

    public function getSubheading(): ?string
    {
        return 'Susun kode, nama, dan kelompok mata pelajaran untuk penilaian rapor.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Mata Pelajaran')
                ->icon('heroicon-o-plus-circle')
                ->extraAttributes(['class' => 'raport-desktop-only-action']),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\KelasResource\Pages;

use App\Filament\Admin\Resources\KelasResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKelas extends ListRecords
{
    protected static string $resource = KelasResource::class;

    public function getSubheading(): ?string
    {
        return 'Atur ruang kelas, tingkat, wali kelas, dan daftar peserta aktif.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Semua Kelas')
                ->icon('heroicon-o-plus-circle')
                ->extraAttributes(['class' => 'raport-desktop-only-action']),
        ];
    }
}

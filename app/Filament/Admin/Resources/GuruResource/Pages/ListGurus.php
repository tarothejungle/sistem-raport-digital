<?php

namespace App\Filament\Admin\Resources\GuruResource\Pages;

use App\Filament\Admin\Resources\GuruResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGurus extends ListRecords
{
    protected static string $resource = GuruResource::class;

    public function getSubheading(): ?string
    {
        return 'Kelola akun guru, kontak, dan status akses input nilai.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Guru')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}

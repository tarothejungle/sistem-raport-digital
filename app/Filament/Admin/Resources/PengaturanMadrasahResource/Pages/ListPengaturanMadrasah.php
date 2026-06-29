<?php

namespace App\Filament\Admin\Resources\PengaturanMadrasahResource\Pages;

use App\Filament\Admin\Resources\PengaturanMadrasahResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPengaturanMadrasahs extends ListRecords
{
    protected static string $resource = PengaturanMadrasahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Isi Pengaturan Madrasah'),
        ];
    }
}
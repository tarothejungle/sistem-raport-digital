<?php

namespace App\Filament\Admin\Resources\MataPelajaranResource\Pages;

use App\Filament\Admin\Resources\MataPelajaranResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMataPelajarans extends ListRecords
{
    protected static string $resource = MataPelajaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Mata Pelajaran'),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\NilaiResource\Pages;

use App\Filament\Admin\Resources\NilaiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNilais extends ListRecords
{
    protected static string $resource = NilaiResource::class;

    public function getSubheading(): ?string
    {
        return 'Pantau, filter, dan ubah nilai final siswa berdasarkan penugasan pengajar.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Nilai')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}

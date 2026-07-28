<?php

namespace App\Filament\Admin\Resources\TahunAjaranResource\Pages;

use App\Filament\Admin\Resources\TahunAjaranResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTahunAjarans extends ListRecords
{
    protected static string $resource = TahunAjaranResource::class;

    public function getSubheading(): ?string
    {
        return 'Tetapkan periode aktif yang dipakai untuk input nilai, kenaikan kelas, dan cetak rapor.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Set Tahun Ajaran')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}

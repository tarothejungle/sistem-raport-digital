<?php

namespace App\Filament\Admin\Resources\JadwalMengajarResource\Pages;

use App\Filament\Admin\Resources\JadwalMengajarResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJadwalMengajars extends ListRecords
{
    protected static string $resource = JadwalMengajarResource::class;

    public function getSubheading(): ?string
    {
        return 'Atur guru pengampu per mata pelajaran, kelas, dan tahun ajaran. Guru yang ditugaskan otomatis memperoleh hak input nilai untuk kelas tersebut.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Atur Pengajar Kelas')
                ->icon('heroicon-o-user-group')
                ->color('primary'),
        ];
    }
}

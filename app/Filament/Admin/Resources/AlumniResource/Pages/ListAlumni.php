<?php

namespace App\Filament\Admin\Resources\AlumniResource\Pages;

use App\Filament\Admin\Resources\AlumniResource;
use Filament\Resources\Pages\ListRecords;

class ListAlumni extends ListRecords
{
    protected static string $resource = AlumniResource::class;

    public function getSubheading(): ?string
    {
        return 'Lihat siswa yang sudah diluluskan dan kembalikan status bila diperlukan.';
    }
}

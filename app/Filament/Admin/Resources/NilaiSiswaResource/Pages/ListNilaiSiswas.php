<?php

namespace App\Filament\Admin\Resources\NilaiSiswaResource\Pages;

use App\Filament\Admin\Resources\NilaiSiswaResource;
use Filament\Resources\Pages\ListRecords;

class ListNilaiSiswas extends ListRecords
{
    protected static string $resource = NilaiSiswaResource::class;

    public function getSubheading(): ?string
    {
        return 'Lihat nilai final yang sudah dibagikan untuk akun siswa ini.';
    }
}

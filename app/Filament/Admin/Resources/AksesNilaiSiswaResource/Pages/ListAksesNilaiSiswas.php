<?php

namespace App\Filament\Admin\Resources\AksesNilaiSiswaResource\Pages;

use App\Filament\Admin\Resources\AksesNilaiSiswaResource;
use Filament\Resources\Pages\ListRecords;

class ListAksesNilaiSiswas extends ListRecords
{
    protected static string $resource = AksesNilaiSiswaResource::class;

    public function getSubheading(): ?string
    {
        return 'Buka atau tutup akses siswa untuk melihat nilai final di portal.';
    }
}

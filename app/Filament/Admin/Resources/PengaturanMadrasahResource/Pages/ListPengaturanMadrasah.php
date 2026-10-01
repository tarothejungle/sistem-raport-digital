<?php

namespace App\Filament\Admin\Resources\PengaturanMadrasahResource\Pages;

use App\Filament\Admin\Resources\PengaturanMadrasahResource;
use Filament\Resources\Pages\ListRecords;

class ListPengaturanMadrasah extends ListRecords
{
    protected static string $resource = PengaturanMadrasahResource::class;

    public function getSubheading(): ?string
    {
        return 'Lengkapi identitas dan pimpinan madrasah yang tampil pada dokumen rapor.';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}

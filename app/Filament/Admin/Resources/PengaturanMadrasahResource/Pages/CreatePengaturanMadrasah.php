<?php

namespace App\Filament\Admin\Resources\PengaturanMadrasahResource\Pages;

use App\Filament\Admin\Resources\PengaturanMadrasahResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePengaturanMadrasah extends CreateRecord
{
    protected static string $resource = PengaturanMadrasahResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Pengaturan madrasah berhasil disimpan.';
    }
}

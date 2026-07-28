<?php

namespace App\Filament\Admin\Resources\PengaturanMadrasahResource\Pages;

use App\Filament\Admin\Resources\PengaturanMadrasahResource;
use Filament\Resources\Pages\EditRecord;

class EditPengaturanMadrasah extends EditRecord
{
    protected static string $resource = PengaturanMadrasahResource::class;

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Pengaturan madrasah berhasil diperbarui.';
    }
}

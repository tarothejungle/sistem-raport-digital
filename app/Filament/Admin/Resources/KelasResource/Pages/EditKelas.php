<?php

namespace App\Filament\Admin\Resources\KelasResource\Pages;

use App\Filament\Admin\Resources\KelasResource;
use App\Models\Kelas;
use App\Services\KelasService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditKelas extends EditRecord
{
    protected static string $resource = KelasResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Kelas $kelas */
        $kelas = $record;

        return app(KelasService::class)->update($kelas, $data);
    }
}

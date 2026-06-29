<?php

namespace App\Filament\Admin\Resources\SiswaResource\Pages;

use App\Filament\Admin\Resources\SiswaResource;
use App\Models\Siswa;
use App\Services\SiswaService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSiswa extends EditRecord
{
    protected static string $resource = SiswaResource::class;

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Siswa $siswa */
        $siswa = $this->getRecord()->loadMissing('user');

        return [
            ...$data,
            'username' => $siswa->user?->username,
            'email' => $siswa->user?->email,
        ];
    }
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Siswa $siswa */
        $siswa = $record;

        return app(SiswaService::class)->update($siswa, $data);
    }
}

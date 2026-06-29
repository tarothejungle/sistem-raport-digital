<?php

namespace App\Filament\Admin\Resources\GuruResource\Pages;

use App\Filament\Admin\Resources\GuruResource;
use App\Models\Guru;
use App\Services\GuruService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditGuru extends EditRecord
{
    protected static string $resource = GuruResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Guru $guru */
        $guru = $this->getRecord()->loadMissing('user');

        return [
            ...$data,
            'name' => $guru->user?->name,
            'username' => $guru->user?->username,
            'email' => $guru->user?->email,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Guru $guru */
        $guru = $record;

        return app(GuruService::class)->update($guru, $data);
    }
}

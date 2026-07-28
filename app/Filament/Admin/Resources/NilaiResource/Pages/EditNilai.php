<?php

namespace App\Filament\Admin\Resources\NilaiResource\Pages;

use App\Filament\Admin\Resources\NilaiResource;
use App\Models\Nilai;
use App\Services\NilaiService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNilai extends EditRecord
{
    protected static string $resource = NilaiResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Nilai $nilai */
        $nilai = $this->getRecord();

        return app(NilaiService::class)->prepareForPersistence($data, $nilai);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

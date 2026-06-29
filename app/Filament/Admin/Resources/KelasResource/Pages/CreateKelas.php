<?php

namespace App\Filament\Admin\Resources\KelasResource\Pages;

use App\Filament\Admin\Resources\KelasResource;
use App\Models\Kelas;
use App\Services\KelasService;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateKelas extends CreateRecord
{
    protected static string $resource = KelasResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var Kelas $kelas */
        $kelas = app(KelasService::class)->create($data);

        return $kelas;
    }

    public function save(): void
    {
        $this->create();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Data Kelas')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->submit('save'),

            $this->getCreateAnotherFormAction()
                ->label('Simpan Data dan Buat Data Lain')
                ->icon('heroicon-o-plus-circle'),

            Action::make('kembali')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(static::getResource()::getUrl('index')),
        ];
    }
}

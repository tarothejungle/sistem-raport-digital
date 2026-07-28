<?php

namespace App\Filament\Admin\Resources\GuruResource\Pages;

use App\Filament\Admin\Resources\GuruResource;
use App\Models\Guru;
use App\Services\GuruService;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateGuru extends CreateRecord
{
    protected static string $resource = GuruResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var Guru $guru */
        $guru = app(GuruService::class)->create($data);

        return $guru;
    }

    public function save(): void
    {
        $this->create();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Data Guru')
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

<?php

namespace App\Filament\Admin\Resources\SiswaResource\Pages;

use App\Filament\Admin\Resources\SiswaResource;
use App\Models\Siswa;
use App\Services\SiswaService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class CreateSiswa extends CreateRecord
{
    protected static string $resource = SiswaResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var Siswa $siswa */
        $siswa = app(SiswaService::class)->create($data);

        return $siswa;
    }

    public function save(): void
    {
        $this->create();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Data Siswa')
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

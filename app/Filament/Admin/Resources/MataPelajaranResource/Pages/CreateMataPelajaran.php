<?php

namespace App\Filament\Admin\Resources\MataPelajaranResource\Pages;

use App\Filament\Admin\Resources\MataPelajaranResource;
use App\Models\MataPelajaran;
use App\Services\MataPelajaranService;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateMataPelajaran extends CreateRecord
{
    protected static string $resource = MataPelajaranResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        /** @var MataPelajaran $mataPelajaran */
        $mataPelajaran = app(MataPelajaranService::class)->create($data);

        return $mataPelajaran;
    }

    public function save(): void
    {
        $this->create();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Data Mata Pelajaran')
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

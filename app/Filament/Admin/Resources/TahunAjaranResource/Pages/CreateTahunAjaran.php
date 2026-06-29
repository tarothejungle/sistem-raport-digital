<?php

namespace App\Filament\Admin\Resources\TahunAjaranResource\Pages;

use App\Filament\Admin\Resources\TahunAjaranResource;
use App\Models\TahunAjaran;
use App\Services\TahunAjaranService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;
use Override;

class CreateTahunAjaran extends CreateRecord
{
    protected static string $resource = TahunAjaranResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        /** @var TahunAjaran $tahunAjaran */
        $tahunAjaran = app(TahunAjaranService::class)->create($data);

        return $tahunAjaran;
    }

    public function save(): void
    {
        $this->create();

    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Data Berhasil Disimpan')
            ->success();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Tahun Ajaran')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->submit('save'),

            Action::make('kembali')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(static::getResource()::getUrl('index')),
        ];
    }
}

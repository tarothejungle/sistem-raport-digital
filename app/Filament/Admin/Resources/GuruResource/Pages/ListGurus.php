<?php

namespace App\Filament\Admin\Resources\GuruResource\Pages;

use App\Filament\Admin\Resources\GuruResource;
use App\Services\AbsensiGuruSyncService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Throwable;

class ListGurus extends ListRecords
{
    protected static string $resource = GuruResource::class;

    public function getSubheading(): ?string
    {
        return 'Kelola akun guru, kontak, dan status akses input nilai.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tarikDataAbsensi')
                ->label('Sync Guru')
                ->icon('heroicon-o-arrow-down-tray')
                ->requiresConfirmation()
                ->modalHeading('Sync data guru dari Absensi?')
                ->modalDescription('Data guru baru akan ditambahkan dan data guru lama akan diperbarui dari sistem Absensi.')
                ->extraAttributes(['class' => 'raport-desktop-only-action'])
                ->action(function (): void {
                    try {
                        $result = app(AbsensiGuruSyncService::class)->sync();

                        Notification::make()
                            ->title('Data Berhasil Ditarik')
                            ->body($result['created'].' guru baru ditambahkan, '.$result['updated'].' data diperbarui.')
                            ->success()
                            ->send();
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('Data Gagal Ditarik')
                            ->body('Sinkronisasi gagal. Periksa konfigurasi atau log aplikasi lalu coba kembali.')
                            ->danger()
                            ->send();
                    }
                }),

            CreateAction::make()
                ->label('Tambah Guru')
                ->icon('heroicon-o-plus-circle')
                ->extraAttributes(['class' => 'raport-desktop-only-action']),
        ];
    }
}

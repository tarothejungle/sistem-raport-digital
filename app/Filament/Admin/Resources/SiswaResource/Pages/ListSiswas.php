<?php

namespace App\Filament\Admin\Resources\SiswaResource\Pages;

use App\Filament\Admin\Resources\SiswaResource;
use App\Services\SiswaImportService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Validation\ValidationException;
use Throwable;

class ListSiswas extends ListRecords
{
    protected static string $resource = SiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadTemplate')
                ->label('Template Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(url('/templates/template-import-siswa.xlsx'))
                ->openUrlInNewTab(),
            Actions\Action::make('importExcel')
                ->label('Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->modalHeading('Import Data Siswa')
                ->modalDescription('Data akan ditambahkan atau diperbarui berdasarkan NISN. File tidak disimpan permanen di server.')
                ->modalSubmitActionLabel('Mulai Import')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('File Data Siswa')
                        ->required()
                        ->storeFiles(false)
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/csv',
                            'text/plain',
                            'application/csv',
                            'text/comma-separated-values',
                        ])
                        ->maxSize(5120)
                        ->helperText('Gunakan .xlsx atau .csv dengan kolom NISN, Nama Lengkap, dan Kelas. Ukuran maksimal 5 MB. Nama kelas harus sudah ada di menu Kelas.'),
                ])
                ->action(function (array $data): void {
                    try {
                        $summary = app(SiswaImportService::class)->importUploadedFile($data['file'] ?? null);
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->danger()
                            ->persistent()
                            ->title('Import siswa gagal')
                            ->body(collect($exception->errors())->flatten()->take(10)->implode(' '))
                            ->send();

                        return;
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->danger()
                            ->persistent()
                            ->title('Import siswa gagal')
                            ->body('Terjadi kendala saat membaca file. Detail kesalahan dicatat di storage/logs/laravel.log.')
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->persistent()
                        ->title('Import siswa selesai')
                        ->body(sprintf(
                            '%d data baru ditambahkan dan %d data diperbarui. Akun siswa baru memakai kata sandi awal berupa NISN.',
                            $summary['created'],
                            $summary['updated'],
                        ))
                        ->send();
                }),
            Actions\CreateAction::make()
                ->label('Tambah Siswa'),
        ];
    }
}

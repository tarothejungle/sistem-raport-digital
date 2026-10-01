<?php

namespace App\Filament\Admin\Resources\SiswaResource\Pages;

use App\Filament\Admin\Resources\SiswaResource;
use App\Models\Kelas;
use App\Services\SiswaImportService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Throwable;

class ListSiswas extends ListRecords
{
    protected static string $resource = SiswaResource::class;

    public function getSubheading(): ?string
    {
        return 'Kelola data peserta didik, akun portal, import Excel/JSON, dan akses nilai.';
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('downloadTemplate')
                    ->label('Template Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(url('/templates/template-import-siswa.xlsx'))
                    ->openUrlInNewTab(),
                Action::make('importExcel')
                    ->label('Import Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->modalHeading('Import Data Siswa')
                    ->modalDescription('Data akan ditambahkan atau diperbarui berdasarkan NISN. File tidak disimpan permanen di server.')
                    ->modalSubmitActionLabel('Mulai Import')
                    ->schema([
                        FileUpload::make('file')
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
                                '%d data baru ditambahkan dan %d data diperbarui. Atur email aktif dan kata sandi sebelum memberi akses portal kepada siswa baru.',
                                $summary['created'],
                                $summary['updated'],
                            ))
                            ->send();
                    }),
                Action::make('importJson')
                    ->label('Import Siswa (.json)')
                    ->icon('heroicon-o-code-bracket-square')
                    ->color('info')
                    ->extraAttributes([
                        'id' => 'raport-import-siswa-json-action',
                        'class' => 'raport-desktop-only-action',
                        'x-on:click.capture' => '$store.sidebar.close()',
                    ])
                    ->modalHeading('Pratinjau Import Siswa dari JSON EMIS')
                    ->modalDescription('Belum ada data yang disimpan sebelum tombol Konfirmasi & Import ditekan.')
                    ->modalSubmitActionLabel('Konfirmasi & Import')
                    ->modalWidth('7xl')
                    ->extraModalWindowAttributes(['class' => 'raport-siswa-json-import-modal'])
                    ->steps([
                        Step::make('Unggah JSON')
                            ->description('Baca data tanpa menyimpan ke database')
                            ->schema([
                                FileUpload::make('file')
                                    ->label('File JSON EMIS')
                                    ->required()
                                    ->storeFiles(false)
                                    ->acceptedFileTypes([
                                        'application/json',
                                        'application/octet-stream',
                                        'text/json',
                                        'text/plain',
                                    ])
                                    ->maxSize(5120)
                                    ->live()
                                    ->afterStateUpdated(function (mixed $state, Set $set): void {
                                        if (blank($state)) {
                                            $set('preview_students', []);
                                            $set('selected_students', []);
                                            $set('class_mappings', []);
                                            $set('preview_skipped', 0);

                                            return;
                                        }

                                        $preview = app(SiswaImportService::class)->previewUploadedJson($state);
                                        $set('preview_students', $preview['students']);
                                        $set('selected_students', collect($preview['students'])->pluck('selection_key')->all());
                                        $set('preview_skipped', $preview['skipped']);
                                        $set('class_mappings', collect($preview['source_classes'])
                                            ->map(static fn (string $name, string $key): array => [
                                                'source_key' => $key,
                                                'source_class' => $name,
                                                'kelas_id' => $preview['suggested_mappings'][$key] ?? null,
                                            ])
                                            ->values()
                                            ->all());
                                    })
                                    ->helperText('Gunakan file .json hasil scraping EMIS. Ukuran maksimal 5 MB.'),
                                Hidden::make('preview_students')->default([]),
                                Hidden::make('preview_skipped')->default(0),
                                Placeholder::make('preview_summary')
                                    ->label('Hasil pembacaan')
                                    ->content(static function (Get $get): string {
                                        $count = count($get('preview_students') ?? []);
                                        $skipped = (int) ($get('preview_skipped') ?? 0);

                                        return "{$count} siswa siap ditinjau. {$skipped} data tidak lengkap/duplikat akan dilewati.";
                                    }),
                            ]),
                        Step::make('Pilih & Petakan')
                            ->description('Pilih siswa dan kelas tujuan yang sudah terdaftar')
                            ->schema([
                                CheckboxList::make('selected_students')
                                    ->label('Siswa yang akan diimpor')
                                    ->extraAttributes(['class' => 'raport-siswa-json-selection'])
                                    ->options(static fn (Get $get): array => collect($get('preview_students') ?? [])
                                        ->mapWithKeys(static fn (array $student): array => [
                                            $student['selection_key'] => sprintf(
                                                '%s - %s (%s)',
                                                $student['nisn'] ?? 'Belum ada NISN',
                                                $student['nama_lengkap'],
                                                $student['source_class'],
                                            ),
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->bulkToggleable()
                                    ->columns(2)
                                    ->required(),
                                Repeater::make('class_mappings')
                                    ->label('Pemetaan rombel EMIS ke kelas aplikasi')
                                    ->schema([
                                        Hidden::make('source_key')->required(),
                                        TextInput::make('source_class')
                                            ->label('Rombel dari EMIS')
                                            ->disabled()
                                            ->dehydrated(),
                                        Select::make('kelas_id')
                                            ->label('Kelas Tujuan')
                                            ->options(fn (): array => Kelas::query()
                                                ->orderBy('tingkat')
                                                ->orderBy('nama_kelas')
                                                ->pluck('nama_kelas', 'id')
                                                ->all())
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                    ])
                                    ->columns(2)
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false),
                            ]),
                        Step::make('Konfirmasi')
                            ->description('Periksa jumlah data sebelum disimpan')
                            ->schema([
                                Placeholder::make('confirmation')
                                    ->hiddenLabel()
                                    ->content(static function (Get $get): HtmlString {
                                        $selected = count($get('selected_students') ?? []);
                                        $classes = count($get('class_mappings') ?? []);

                                        return new HtmlString(
                                            "<strong>{$selected} siswa</strong> akan ditambahkan atau diperbarui ke <strong>{$classes} pemetaan kelas</strong>. Database baru berubah setelah tombol <strong>Konfirmasi &amp; Import</strong> ditekan.",
                                        );
                                    }),
                            ]),
                    ])
                    ->action(function (array $data): void {
                        try {
                            $classMappings = collect($data['class_mappings'] ?? [])
                                ->mapWithKeys(static fn (array $mapping): array => [
                                    $mapping['source_key'] => $mapping['kelas_id'] ?? null,
                                ])
                                ->all();
                            $summary = app(SiswaImportService::class)->importUploadedJsonSelection(
                                $data['file'] ?? null,
                                $data['selected_students'] ?? [],
                                $classMappings,
                            );
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->persistent()
                                ->title('Import siswa JSON gagal')
                                ->body(collect($exception->errors())->flatten()->take(10)->implode(' '))
                                ->send();

                            return;
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->danger()
                                ->persistent()
                                ->title('Import siswa JSON gagal')
                                ->body('Terjadi kendala saat membaca file. Detail kesalahan dicatat di storage/logs/laravel.log.')
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->success()
                            ->persistent()
                            ->title('Import siswa JSON selesai')
                            ->body(sprintf(
                                '%d data baru ditambahkan, %d data diperbarui, dan %d data tidak lengkap/duplikat dilewati.',
                                $summary['created'],
                                $summary['updated'],
                                $summary['skipped'],
                            ))
                            ->send();
                    }),
            ])
                ->label('Export/Download')
                ->icon('heroicon-o-arrows-up-down')
                ->color('gray')
                ->button()
                ->extraAttributes(['class' => 'raport-desktop-only-action']),
            CreateAction::make()
                ->label('Tambah Siswa')
                ->icon('heroicon-o-plus-circle')
                ->extraAttributes(['class' => 'raport-desktop-only-action']),
        ];
    }
}

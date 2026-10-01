<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\Concerns\AdminOnlyResource;
use App\Filament\Admin\Resources\KelasResource\Pages\CreateKelas;
use App\Filament\Admin\Resources\KelasResource\Pages\EditKelas;
use App\Filament\Admin\Resources\KelasResource\Pages\ListKelas;
use App\Models\Guru;
use App\Models\Kelas;
use App\Services\KelasService;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class KelasResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = Kelas::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Data Kelas';

    protected static ?string $pluralModelLabel = 'Data Kelas';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama_kelas')
                ->label('Nama Kelas')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true)
                ->autofocus(),

            TextInput::make('tingkat')
                ->label('Tingkat')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->maxValue(12)
                ->required(),

            Select::make('wali_kelas_id')
                ->label('Wali Kelas')
                ->options(static fn (): array => Guru::query()
                    ->with('user')
                    ->get()
                    ->sortBy(static fn (Guru $guru): string => $guru->nama)
                    ->mapWithKeys(static fn (Guru $guru): array => [
                        $guru->getKey() => sprintf(
                            '%s (@%s)',
                            $guru->nama,
                            $guru->user?->username ?? '-',
                        ),
                    ])
                    ->all())
                ->searchable()
                ->preload()
                ->placeholder('Belum ditentukan'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'raport-mobile-full-search-table'])
            ->defaultSort('tingkat')
            ->columns([
                TextColumn::make('nama_kelas')
                    ->label('Kelas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tingkat')
                    ->label('Tingkat')
                    ->sortable(),
                TextColumn::make('waliKelas.nama')
                    ->label('Wali Kelas')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('siswa_aktif_count')
                    ->label('Siswa Aktif')
                    ->counts('siswaAktif')
                    ->badge(),
                TextColumn::make('jadwal_mengajars_count')
                    ->label('Jadwal')
                    ->counts('jadwalMengajars')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('tingkat')
                    ->options(collect(range(1, 12))->mapWithKeys(
                        static fn (int $tingkat): array => [$tingkat => "Tingkat {$tingkat}"],
                    )->all()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, Kelas $record): void {
                        if (
                            ! $record->siswas()->exists()
                            && ! $record->jadwalMengajars()->exists()
                            && ! $record->riwayatKelasSiswas()->exists()
                        ) {
                            return;
                        }

                        Notification::make()
                            ->danger()
                            ->title('Kelas tidak dapat dihapus')
                            ->body('Hapus atau pindahkan data siswa, jadwal mengajar, dan riwayat kelas yang masih terkait terlebih dahulu.')
                            ->send();

                        $action->cancel();
                    })
                    ->using(static fn (Kelas $record): bool => app(KelasService::class)->delete($record)),
            ])
            ->toolbarActions([
                BulkAction::make('deleteSelected')
                    ->label('Hapus Terpilih')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus kelas terpilih?')
                    ->modalDescription('Kelas yang masih memiliki siswa, jadwal mengajar, atau riwayat kelas tidak akan dihapus.')
                    ->modalSubmitActionLabel('Ya, hapus')
                    ->action(function ($records): void {
                        $deleted = 0;
                        $blocked = 0;

                        foreach ($records as $record) {
                            try {
                                if (app(KelasService::class)->delete($record)) {
                                    $deleted++;
                                }
                            } catch (ValidationException) {
                                $blocked++;
                            }
                        }

                        $notification = Notification::make()
                            ->title($blocked > 0 ? 'Sebagian kelas tidak dapat dihapus' : 'Kelas terpilih berhasil dihapus')
                            ->body("{$deleted} kelas dihapus. {$blocked} kelas dilewati karena masih memiliki data terkait.");

                        ($blocked > 0 ? $notification->warning() : $notification->success())->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKelas::route('/'),
            'create' => CreateKelas::route('/create'),
            'edit' => EditKelas::route('/{record}/edit'),
        ];
    }
}

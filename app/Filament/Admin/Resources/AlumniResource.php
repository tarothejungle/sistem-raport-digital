<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AlumniResource\Pages;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AlumniResource extends Resource
{
    protected static ?string $model = Siswa::class;

    protected static ?string $slug = 'alumni';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Data Alumni';

    protected static ?string $modelLabel = 'Alumni';

    protected static ?string $pluralModelLabel = 'Data Alumni';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->alumni()
            ->with(['kelas', 'tahunLulus', 'user'])
            ->withCount('nilais');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('tanggal_lulus', 'desc')
            ->columns([
                TextColumn::make('nisn')
                    ->label('NISN')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('nama_lengkap')
                    ->label('Nama Alumni')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas Terakhir')
                    ->badge()
                    ->sortable(),
                TextColumn::make('tahunLulus.label')
                    ->label('Tahun Lulus')
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('tanggal_lulus')
                    ->label('Tanggal Lulus')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('nilais_count')
                    ->label('Data Nilai')
                    ->badge(),
                TextColumn::make('keterangan_alumni')
                    ->label('Keterangan')
                    ->limit(40)
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('tahun_lulus_id')
                    ->label('Tahun Lulus')
                    ->options(static fn (): array => TahunAjaran::query()
                        ->orderByDesc('nama')
                        ->orderByDesc('semester')
                        ->get()
                        ->mapWithKeys(static fn (TahunAjaran $tahunAjaran): array => [
                            $tahunAjaran->getKey() => $tahunAjaran->label,
                        ])
                        ->all())
                    ->searchable(),
            ])
            ->recordActions([
                Action::make('restoreActive')
                    ->label('Aktifkan Lagi')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Aktifkan kembali siswa ini?')
                    ->modalDescription('Status alumni akan dibatalkan dan siswa kembali muncul di menu Siswa dengan kelas terakhirnya.')
                    ->action(function (Siswa $record): void {
                        $record->update([
                            'status' => Siswa::STATUS_AKTIF,
                            'tahun_lulus_id' => null,
                            'tanggal_lulus' => null,
                            'keterangan_alumni' => null,
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Status alumni dibatalkan')
                            ->body("{$record->nama_lengkap} kembali aktif sebagai siswa.")
                            ->send();
                    }),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()
            ->alumni()
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAlumni::route('/'),
        ];
    }
}

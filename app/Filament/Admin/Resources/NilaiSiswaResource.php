<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\NilaiSiswaResource\Pages\ListNilaiSiswas;
use App\Models\Nilai;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NilaiSiswaResource extends Resource
{
    protected static ?string $model = Nilai::class;

    protected static ?string $slug = 'nilai-siswa';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Portal Siswa';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Nilai Saya';

    protected static ?string $modelLabel = 'Nilai Saya';

    protected static ?string $pluralModelLabel = 'Nilai Saya';

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->isSiswa() && $user->siswa?->can_view_nilai === true;
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

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $siswaId = auth()->user()?->siswa?->getKey();

        return parent::getEloquentQuery()
            ->with([
                'jadwalMengajar.mataPelajaran',
                'jadwalMengajar.guru.user',
                'jadwalMengajar.kelas',
                'jadwalMengajar.tahunAjaran',
            ])
            ->where('siswa_id', $siswaId ?: 0)
            ->where('is_submitted', true);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('jadwalMengajar.mataPelajaran.kelompok')
                    ->label('Kelompok')
                    ->badge(),
                TextColumn::make('jadwalMengajar.mataPelajaran.nama_mapel')
                    ->label('Mata Pelajaran')
                    ->searchable(),
                TextColumn::make('jadwalMengajar.guru.user.name')
                    ->label('Guru'),
                TextColumn::make('kkm')
                    ->label('KKM'),
                TextColumn::make('nilai_angka')
                    ->label('Angka')
                    ->badge(),
                TextColumn::make('predikat')
                    ->label('Predikat')
                    ->badge()
                    ->color(static fn (?string $state): string => match ($state) {
                        'A' => 'success',
                        'B' => 'info',
                        'C' => 'warning',
                        'D' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('deskripsi')
                    ->label('Deskripsi')
                    ->wrap(),
                TextColumn::make('jadwalMengajar.tahunAjaran.label')
                    ->label('Tahun Ajaran')
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNilaiSiswas::route('/'),
        ];
    }
}

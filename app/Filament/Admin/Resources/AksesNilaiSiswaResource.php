<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AksesNilaiSiswaResource\Pages;
use App\Models\Siswa;
use App\Services\StudentAccessService;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AksesNilaiSiswaResource extends Resource
{
    protected static ?string $model = Siswa::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Akses Nilai Siswa';

    protected static ?string $modelLabel = 'Akses Nilai Siswa';

    protected static ?string $pluralModelLabel = 'Akses Nilai Siswa';

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || ($user?->isGuru() && $user->guru?->can_input_nilai === true);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof Siswa
            && app(StudentAccessService::class)->canManage($record);
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
        $query = parent::getEloquentQuery()->with(['kelas', 'user']);
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return $query;
        }

        $guruId = $user?->guru?->getKey();

        if ($user?->isGuru() && $guruId !== null) {
            return $query->whereHas(
                'kelas.jadwalMengajars',
                static fn (Builder $jadwalQuery): Builder => $jadwalQuery->where('guru_id', $guruId),
            );
        }

        return $query->whereKey(0);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nama_lengkap')
            ->columns([
                Tables\Columns\TextColumn::make('nisn')
                    ->label('NISN / ID Login')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('nama_lengkap')
                    ->label('Nama Siswa')
                    ->searchable(),
                Tables\Columns\TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas')
                    ->badge(),
                Tables\Columns\IconColumn::make('can_view_nilai')
                    ->label('Boleh Melihat Nilai')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('ubahAkses')
                    ->label(static fn (Siswa $record): string => $record->can_view_nilai ? 'Cabut Akses' : 'Izinkan Akses')
                    ->icon(static fn (Siswa $record): string => $record->can_view_nilai ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(static fn (Siswa $record): string => $record->can_view_nilai ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(static fn (Siswa $record): string => $record->can_view_nilai ? 'Cabut akses nilai siswa' : 'Izinkan akses nilai siswa')
                    ->modalDescription(static fn (Siswa $record): string => $record->can_view_nilai
                        ? 'Siswa tidak dapat lagi masuk ke portal untuk melihat nilai setelah tindakan ini.'
                        : 'Siswa dapat masuk ke portal dan melihat nilai yang sudah difinalisasi setelah tindakan ini.')
                    ->disabled(static fn (Siswa $record): bool => $record->user_id === null)
                    ->tooltip(static fn (Siswa $record): ?string => $record->user_id === null
                        ? 'Akun siswa belum dibuat oleh admin.'
                        : null)
                    ->action(static function (Siswa $record): void {
                        app(StudentAccessService::class)->setViewingAccess(
                            $record,
                            ! $record->can_view_nilai,
                        );
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAksesNilaiSiswas::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AksesNilaiSiswaResource\Pages\ListAksesNilaiSiswas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\StudentAccessService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class AksesNilaiSiswaResource extends Resource
{
    protected static ?string $model = Siswa::class;

    protected static ?string $slug = 'akses-nilai-siswa';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 6;

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
                static fn (Builder $jadwalQuery): Builder => $jadwalQuery
                    ->where('guru_id', $guruId)
                    ->whereIn('tahun_ajaran_id', TahunAjaran::query()
                        ->select('id')
                        ->where('is_active', true)),
            );
        }

        return $query->whereKey(0);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'raport-mobile-full-search-table'])
            ->defaultSort('nama_lengkap')
            ->columns([
                TextColumn::make('nisn')
                    ->label('NISN / ID Login')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('nama_lengkap')
                    ->label('Nama Siswa')
                    ->searchable(),
                TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas')
                    ->badge(),
                IconColumn::make('can_view_nilai')
                    ->label('Boleh Melihat Nilai')
                    ->boolean(),
            ])
            ->recordActions([
                Action::make('ubahAkses')
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
            ])
            ->toolbarActions([
                BulkAction::make('allowSelected')
                    ->label('Izinkan Akses Terpilih')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Izinkan akses nilai siswa terpilih?')
                    ->modalDescription('Siswa terpilih dapat masuk ke portal dan melihat nilai yang sudah difinalisasi.')
                    ->modalSubmitActionLabel('Ya, izinkan')
                    ->action(static function ($records): void {
                        self::bulkSetViewingAccess($records, true);
                    })
                    ->deselectRecordsAfterCompletion(),

                BulkAction::make('revokeSelected')
                    ->label('Cabut Akses Terpilih')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cabut akses nilai siswa terpilih?')
                    ->modalDescription('Siswa terpilih tidak dapat lagi melihat nilai di portal.')
                    ->modalSubmitActionLabel('Ya, cabut')
                    ->action(static function ($records): void {
                        self::bulkSetViewingAccess($records, false);
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    private static function bulkSetViewingAccess($records, bool $enabled): void
    {
        $updated = 0;
        $blocked = 0;

        foreach ($records as $record) {
            try {
                app(StudentAccessService::class)->setViewingAccess($record, $enabled);
                $updated++;
            } catch (AuthorizationException|ValidationException) {
                $blocked++;
            }
        }

        $actionLabel = $enabled ? 'diizinkan' : 'dicabut';
        $notification = Notification::make()
            ->title($blocked > 0 ? 'Sebagian akses siswa tidak dapat diubah' : 'Akses siswa berhasil diubah')
            ->body("{$updated} akses siswa berhasil {$actionLabel}. {$blocked} siswa dilewati karena tidak memenuhi syarat.");

        ($blocked > 0 ? $notification->warning() : $notification->success())->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAksesNilaiSiswas::route('/'),
        ];
    }
}

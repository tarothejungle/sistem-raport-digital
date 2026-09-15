<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\Concerns\AdminOnlyResource;
use App\Filament\Admin\Resources\JadwalMengajarResource\Pages\CreateJadwalMengajar;
use App\Filament\Admin\Resources\JadwalMengajarResource\Pages\ListJadwalMengajars;
use App\Models\JadwalMengajar;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class JadwalMengajarResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = JadwalMengajar::class;

    protected static ?string $slug = 'jadwal-mengajar';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Pengajar Kelas';

    protected static ?string $modelLabel = 'Penugasan Pengajar';

    protected static ?string $pluralModelLabel = 'Pengajar Kelas';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('kelas_id')
            ->defaultGroup('kelas.nama_kelas')
            ->groups([
                Group::make('kelas.nama_kelas')
                    ->label('Kelas')
                    ->collapsible(),
            ])
            ->columns([
                TextColumn::make('mataPelajaran.nama_mapel')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('mataPelajaran.kelompok')
                    ->label('Kelompok')
                    ->badge(),
                TextColumn::make('guru.user.name')
                    ->label('Guru Pengampu')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas')
                    ->badge()
                    ->sortable(),
                TextColumn::make('tahunAjaran.nama')
                    ->label('Tahun Ajaran')
                    ->sortable(),
                TextColumn::make('tahunAjaran.semester')
                    ->label('Semester')
                    ->badge(),
                TextColumn::make('nilais_count')
                    ->label('Nilai')
                    ->counts('nilais')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('tahunAjaran')
                    ->relationship('tahunAjaran', 'nama')
                    ->label('Tahun Ajaran')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('kelas')
                    ->relationship('kelas', 'nama_kelas')
                    ->label('Kelas')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->before(function (DeleteAction $action, JadwalMengajar $record): void {
                        if (! $record->nilais()->exists()) {
                            return;
                        }

                        Notification::make()
                            ->danger()
                            ->title('Penugasan tidak dapat dihapus')
                            ->body('Hapus data nilai yang menggunakan penugasan ini terlebih dahulu.')
                            ->send();

                        $action->cancel();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJadwalMengajars::route('/'),
            'create' => CreateJadwalMengajar::route('/create'),
        ];
    }
}

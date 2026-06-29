<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\Concerns\AdminOnlyResource;
use App\Filament\Admin\Resources\JadwalMengajarResource\Pages;
use App\Models\JadwalMengajar;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class JadwalMengajarResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = JadwalMengajar::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Akademik';

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
                Tables\Columns\TextColumn::make('mataPelajaran.nama_mapel')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('mataPelajaran.kelompok')
                    ->label('Kelompok')
                    ->badge(),
                Tables\Columns\TextColumn::make('guru.user.name')
                    ->label('Guru Pengampu')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tahunAjaran.nama')
                    ->label('Tahun Ajaran')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tahunAjaran.semester')
                    ->label('Semester')
                    ->badge(),
                Tables\Columns\TextColumn::make('nilais_count')
                    ->label('Nilai')
                    ->counts('nilais')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tahunAjaran')
                    ->relationship('tahunAjaran', 'nama')
                    ->label('Tahun Ajaran')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('kelas')
                    ->relationship('kelas', 'nama_kelas')
                    ->label('Kelas')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, JadwalMengajar $record): void {
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
            'index' => Pages\ListJadwalMengajars::route('/'),
            'create' => Pages\CreateJadwalMengajar::route('/create'),
        ];
    }
}

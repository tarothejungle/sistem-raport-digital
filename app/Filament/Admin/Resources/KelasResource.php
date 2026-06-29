<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\Concerns\AdminOnlyResource;
use App\Filament\Admin\Resources\KelasResource\Pages;
use App\Models\Guru;
use App\Models\Kelas;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class KelasResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = Kelas::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Kelas';

    protected static ?string $pluralModelLabel = 'Kelas';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nama_kelas')
                ->label('Nama Kelas')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true)
                ->autofocus(),

            Forms\Components\TextInput::make('tingkat')
                ->label('Tingkat')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->maxValue(12)
                ->required(),

            Forms\Components\Select::make('wali_kelas_id')
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
            ->defaultSort('tingkat')
            ->columns([
                Tables\Columns\TextColumn::make('nama_kelas')
                    ->label('Kelas')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tingkat')
                    ->label('Tingkat')
                    ->sortable(),
                Tables\Columns\TextColumn::make('waliKelas.nama')
                    ->label('Wali Kelas')
                    ->placeholder('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('siswas_count')
                    ->label('Jumlah Siswa')
                    ->counts('siswas')
                    ->badge(),
                Tables\Columns\TextColumn::make('jadwal_mengajars_count')
                    ->label('Jadwal')
                    ->counts('jadwalMengajars')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tingkat')
                    ->options(collect(range(1, 12))->mapWithKeys(
                        static fn (int $tingkat): array => [$tingkat => "Tingkat {$tingkat}"],
                    )->all()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, Kelas $record): void {
                        if (! $record->siswas()->exists() && ! $record->jadwalMengajars()->exists()) {
                            return;
                        }

                        Notification::make()
                            ->danger()
                            ->title('Kelas tidak dapat dihapus')
                            ->body('Hapus atau pindahkan data siswa dan jadwal mengajar yang masih terkait terlebih dahulu.')
                            ->send();

                        $action->cancel();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKelas::route('/'),
            'create' => Pages\CreateKelas::route('/create'),
            'edit' => Pages\EditKelas::route('/{record}/edit'),
        ];
    }
}

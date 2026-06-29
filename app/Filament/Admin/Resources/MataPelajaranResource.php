<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\Concerns\AdminOnlyResource;
use App\Filament\Admin\Resources\MataPelajaranResource\Pages;
use App\Models\MataPelajaran;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class MataPelajaranResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = MataPelajaran::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Mata Pelajaran';

    protected static ?string $pluralModelLabel = 'Mata Pelajaran';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('kode_mapel')
                ->label('Kode Mata Pelajaran')
                ->required()
                ->maxLength(30)
                ->dehydrateStateUsing(static fn (?string $state): ?string => filled($state) ? Str::upper($state) : null)
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('nama_mapel')
                ->label('Mata Pelajaran')
                ->required()
                ->maxLength(150),
            Forms\Components\Select::make('kelompok')
                ->label('Kelompok Raport')
                ->options([
                    'A' => 'Kelompok A',
                    'B' => 'Kelompok B',
                ])
                ->default('A')
                ->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nama_mapel')
            ->columns([
                Tables\Columns\TextColumn::make('kode_mapel')
                    ->label('Kode')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama_mapel')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kelompok')
                    ->label('Kelompok')
                    ->badge(),
                Tables\Columns\TextColumn::make('jadwal_mengajars_count')
                    ->label('Jadwal')
                    ->counts('jadwalMengajars')
                    ->badge(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, MataPelajaran $record): void {
                        if (! $record->jadwalMengajars()->exists()) {
                            return;
                        }

                        Notification::make()
                            ->danger()
                            ->title('Mata pelajaran tidak dapat dihapus')
                            ->body('Hapus jadwal mengajar yang terkait terlebih dahulu.')
                            ->send();

                        $action->cancel();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMataPelajarans::route('/'),
            'create' => Pages\CreateMataPelajaran::route('/create'),
            'edit' => Pages\EditMataPelajaran::route('/{record}/edit'),
        ];
    }
}

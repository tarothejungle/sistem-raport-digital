<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\Concerns\AdminOnlyResource;
use App\Filament\Admin\Resources\MataPelajaranResource\Pages\CreateMataPelajaran;
use App\Filament\Admin\Resources\MataPelajaranResource\Pages\EditMataPelajaran;
use App\Filament\Admin\Resources\MataPelajaranResource\Pages\ListMataPelajarans;
use App\Models\MataPelajaran;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class MataPelajaranResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = MataPelajaran::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Mata Pelajaran';

    protected static ?string $pluralModelLabel = 'Mata Pelajaran';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('kode_mapel')
                ->label('Kode Mata Pelajaran')
                ->required()
                ->maxLength(30)
                ->dehydrateStateUsing(static fn (?string $state): ?string => filled($state) ? Str::upper($state) : null)
                ->unique(ignoreRecord: true),
            TextInput::make('nama_mapel')
                ->label('Mata Pelajaran')
                ->required()
                ->maxLength(150),
            Select::make('kelompok')
                ->label('Kelompok Rapor')
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
                TextColumn::make('kode_mapel')
                    ->label('Kode')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nama_mapel')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kelompok')
                    ->label('Kelompok')
                    ->badge(),
                TextColumn::make('jadwal_mengajars_count')
                    ->label('Jadwal')
                    ->counts('jadwalMengajars')
                    ->badge(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, MataPelajaran $record): void {
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
            ])
            ->toolbarActions([
                BulkAction::make('deleteSelected')
                    ->label('Hapus Terpilih')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus mata pelajaran terpilih?')
                    ->modalDescription('Mata pelajaran yang masih memiliki jadwal mengajar tidak akan dihapus.')
                    ->modalSubmitActionLabel('Ya, hapus')
                    ->action(function ($records): void {
                        $deleted = 0;
                        $blocked = 0;

                        foreach ($records as $record) {
                            if ($record->jadwalMengajars()->exists()) {
                                $blocked++;

                                continue;
                            }

                            if ($record->delete()) {
                                $deleted++;
                            }
                        }

                        $notification = Notification::make()
                            ->title($blocked > 0 ? 'Sebagian mata pelajaran tidak dapat dihapus' : 'Mata pelajaran terpilih berhasil dihapus')
                            ->body("{$deleted} mata pelajaran dihapus. {$blocked} mata pelajaran dilewati karena masih memiliki jadwal mengajar.");

                        ($blocked > 0 ? $notification->warning() : $notification->success())->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMataPelajarans::route('/'),
            'create' => CreateMataPelajaran::route('/create'),
            'edit' => EditMataPelajaran::route('/{record}/edit'),
        ];
    }
}

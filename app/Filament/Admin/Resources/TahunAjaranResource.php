<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\Concerns\AdminOnlyResource;
use App\Filament\Admin\Resources\TahunAjaranResource\Pages\CreateTahunAjaran;
use App\Filament\Admin\Resources\TahunAjaranResource\Pages\EditTahunAjaran;
use App\Filament\Admin\Resources\TahunAjaranResource\Pages\ListTahunAjarans;
use App\Models\TahunAjaran;
use App\Rules\TahunAjaranBerurutan;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class TahunAjaranResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = TahunAjaran::class;

    protected static ?string $slug = 'tahun-ajaran';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'Tahun Ajaran';

    protected static ?string $pluralModelLabel = 'Tahun Ajaran';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')
                ->label('Tahun Ajaran')
                ->placeholder('2025/2026')
                ->required()
                ->maxLength(9)
                ->regex('/^\d{4}\/\d{4}$/')
                ->rule(new TahunAjaranBerurutan)
                ->rules([
                    static fn (Get $get, ?TahunAjaran $record) => Rule::unique('tahun_ajaran', 'nama')
                        ->where('semester', $get('semester'))
                        ->ignore($record),
                ]),
            Select::make('semester')
                ->options([
                    'Ganjil' => 'Ganjil',
                    'Genap' => 'Genap',
                ])
                ->required(),
            Toggle::make('is_active')
                ->label('Jadikan tahun ajaran aktif')
                ->default(false)
                ->helperText('Hanya satu tahun ajaran yang dapat aktif pada satu waktu.')
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nama', 'desc')
            ->columns([
                TextColumn::make('nama')
                    ->label('Tahun Ajaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('semester')
                    ->badge(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('jadwal_mengajars_count')
                    ->label('Jadwal')
                    ->counts('jadwalMengajars')
                    ->badge(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, TahunAjaran $record): void {
                        if (
                            ! $record->jadwalMengajars()->exists()
                            && ! $record->riwayatKelasSiswas()->exists()
                            && ! $record->alumniSiswas()->exists()
                        ) {
                            return;
                        }

                        Notification::make()
                            ->danger()
                            ->title('Tahun ajaran tidak dapat dihapus')
                            ->body('Hapus jadwal mengajar, riwayat kelas, atau data alumni yang masih memakai tahun ajaran ini terlebih dahulu.')
                            ->send();

                        $action->cancel();
                    }),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTahunAjarans::route('/'),
            'create' => CreateTahunAjaran::route('/create'),
            'edit' => EditTahunAjaran::route('/{record}/edit'),
        ];
    }
}

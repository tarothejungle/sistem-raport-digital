<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\Concerns\AdminOnlyResource;
use App\Filament\Admin\Resources\TahunAjaranResource\Pages;
use App\Models\TahunAjaran;
use App\Rules\TahunAjaranBerurutan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class TahunAjaranResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = TahunAjaran::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Tahun Ajaran';

    protected static ?string $pluralModelLabel = 'Tahun Ajaran';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nama')
                ->label('Tahun Ajaran')
                ->placeholder('2025/2026')
                ->required()
                ->maxLength(9)
                ->regex('/^\d{4}\/\d{4}$/')
                ->rule(new TahunAjaranBerurutan())
                ->rules([
                    static fn (Forms\Get $get, ?TahunAjaran $record) => Rule::unique('tahun_ajarans', 'nama')
                        ->where('semester', $get('semester'))
                        ->ignore($record),
                ]),
            Forms\Components\Select::make('semester')
                ->options([
                    'Ganjil' => 'Ganjil',
                    'Genap' => 'Genap',
                ])
                ->required(),
            Forms\Components\Toggle::make('is_active')
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
                Tables\Columns\TextColumn::make('nama')
                    ->label('Tahun Ajaran')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('semester')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('jadwal_mengajars_count')
                    ->label('Jadwal')
                    ->counts('jadwalMengajars')
                    ->badge(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, TahunAjaran $record): void {
                        if (! $record->jadwalMengajars()->exists()) {
                            return;
                        }

                        Notification::make()
                            ->danger()
                            ->title('Tahun ajaran tidak dapat dihapus')
                            ->body('Hapus jadwal mengajar yang masih memakai tahun ajaran ini terlebih dahulu.')
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
            'index' => Pages\ListTahunAjarans::route('/'),
            'create' => Pages\CreateTahunAjaran::route('/create'),
            'edit' => Pages\EditTahunAjaran::route('/{record}/edit'),
        ];
    }
}

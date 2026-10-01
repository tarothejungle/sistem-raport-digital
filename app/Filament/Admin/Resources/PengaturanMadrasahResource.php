<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\Concerns\AdminOnlyResource;
use App\Filament\Admin\Resources\PengaturanMadrasahResource\Pages\CreatePengaturanMadrasah;
use App\Filament\Admin\Resources\PengaturanMadrasahResource\Pages\EditPengaturanMadrasah;
use App\Filament\Admin\Resources\PengaturanMadrasahResource\Pages\ListPengaturanMadrasah;
use App\Models\PengaturanMadrasah;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PengaturanMadrasahResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = PengaturanMadrasah::class;

    protected static ?string $slug = 'pengaturan-madrasah';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Pengaturan Madrasah';

    protected static ?string $pluralModelLabel = 'Pengaturan Madrasah';

    public static function canCreate(): bool
    {
        return auth()->user()?->isAdmin() === true
            && ! PengaturanMadrasah::query()->exists();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Madrasah')
                ->schema([
                    TextInput::make('nama_madrasah')
                        ->label('Nama Madrasah')
                        ->required()
                        ->maxLength(150),

                    FileUpload::make('logo_path')
                        ->label('Logo Madrasah')
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->disk('public')
                        ->directory('madrasah/logo')
                        ->visibility('public')
                        ->fetchFileInformation(false)
                        ->imagePreviewHeight('120')
                        ->maxSize(2048)
                        ->helperText('Format gambar: JPG, JPEG, PNG, atau WEBP. Maksimal 2 MB.'),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make('Pimpinan Madrasah')
                ->schema([
                    TextInput::make('nama_kepala_madrasah')
                        ->label('Nama Kepala Madrasah')
                        ->maxLength(150),

                    TextInput::make('nip_kepala_madrasah')
                        ->label('NIP Kepala Madrasah')
                        ->nullable()
                        ->maxLength(30)
                        ->placeholder('-')
                        ->helperText('Kosongkan atau isi tanda "-" apabila kepala madrasah belum memiliki NIP.'),

                    TextInput::make('kota')
                        ->label('Kota')
                        ->maxLength(100)
                        ->helperText('Contoh: Tangerang.'),

                    FileUpload::make('ttd_kepala_path')
                        ->label('Tanda Tangan Kepala Madrasah')
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->disk('local')
                        ->directory('madrasah/tanda-tangan')
                        ->visibility('private')
                        ->fetchFileInformation(false)
                        ->imagePreviewHeight('120')
                        ->maxSize(2048)
                        ->helperText('Unggah gambar tanda tangan dengan latar transparan bila tersedia.'),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'raport-mobile-full-search-table'])
            ->columns([
                ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->disk('public')
                    ->circular(),

                TextColumn::make('nama_madrasah')
                    ->label('Madrasah')
                    ->searchable(),

                TextColumn::make('nama_kepala_madrasah')
                    ->label('Kepala Madrasah')
                    ->placeholder('-'),

                TextColumn::make('kota')
                    ->label('Kota')
                    ->placeholder('-'),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPengaturanMadrasah::route('/'),
            'create' => CreatePengaturanMadrasah::route('/create'),
            'edit' => EditPengaturanMadrasah::route('/{record}/edit'),
        ];
    }
}

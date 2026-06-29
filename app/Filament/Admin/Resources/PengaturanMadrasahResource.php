<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\Concerns\AdminOnlyResource;
use App\Filament\Admin\Resources\PengaturanMadrasahResource\Pages;
use App\Models\PengaturanMadrasah;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PengaturanMadrasahResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = PengaturanMadrasah::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Pengaturan Madrasah';

    protected static ?string $pluralModelLabel = 'Pengaturan Madrasah';

    public static function canCreate(): bool
    {
        return auth()->user()?->isAdmin() === true
            && ! PengaturanMadrasah::query()->exists();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identitas Madrasah')
                ->schema([
                    Forms\Components\TextInput::make('nama_madrasah')
                        ->label('Nama Madrasah')
                        ->required()
                        ->maxLength(150),

                    Forms\Components\FileUpload::make('logo_path')
                        ->label('Logo Madrasah')
                        ->image()
                        ->disk('public')
                        ->directory('madrasah/logo')
                        ->visibility('public')
                        ->fetchFileInformation(false)
                        ->imagePreviewHeight('120')
                        ->maxSize(2048)
                        ->helperText('Format gambar: JPG, JPEG, PNG, atau WEBP. Maksimal 2 MB.'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Pimpinan Madrasah')
                ->schema([
                    Forms\Components\TextInput::make('nama_kepala_madrasah')
                        ->label('Nama Kepala Madrasah')
                        ->maxLength(150),

                    Forms\Components\TextInput::make('nip_kepala_madrasah')
                        ->label('NIP Kepala Madrasah')
                        ->nullable()
                        ->maxLength(30)
                        ->placeholder('-')
                        ->helperText('Kosongkan atau isi tanda "-" apabila kepala madrasah belum memiliki NIP.'),

                    Forms\Components\TextInput::make('kota')
                        ->label('Kota')
                        ->maxLength(100)
                        ->helperText('Contoh: Tangerang.'),

                    Forms\Components\FileUpload::make('ttd_kepala_path')
                        ->label('Tanda Tangan Kepala Madrasah')
                        ->image()
                        ->disk('public')
                        ->directory('madrasah/tanda-tangan')
                        ->visibility('public')
                        ->fetchFileInformation(false)
                        ->imagePreviewHeight('120')
                        ->maxSize(2048)
                        ->helperText('Unggah gambar tanda tangan dengan latar transparan bila tersedia.'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->disk('public')
                    ->circular(),

                Tables\Columns\TextColumn::make('nama_madrasah')
                    ->label('Madrasah')
                    ->searchable(),

                Tables\Columns\TextColumn::make('nama_kepala_madrasah')
                    ->label('Kepala Madrasah')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('kota')
                    ->label('Kota')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPengaturanMadrasahs::route('/'),
            'create' => Pages\CreatePengaturanMadrasah::route('/create'),
            'edit' => Pages\EditPengaturanMadrasah::route('/{record}/edit'),
        ];
    }
}
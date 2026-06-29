<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\Concerns\AdminOnlyResource;
use App\Filament\Admin\Resources\GuruResource\Pages;
use App\Models\Guru;
use App\Services\GuruService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GuruResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = Guru::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Guru';

    protected static ?string $pluralModelLabel = 'Guru';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data Guru dan Akun Masuk')
                ->description(
                    'Guru masuk menggunakan username. Email digunakan untuk pemulihan kata sandi.',
                )
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Lengkap')
                        ->required()
                        ->maxLength(150),

                    Forms\Components\TextInput::make('username')
                        ->label('Username')
                        ->required()
                        ->maxLength(50)
                        ->rule('regex:/^[A-Za-z0-9._-]{3,50}$/')
                        ->dehydrateStateUsing(
                            static fn (?string $state): ?string => filled($state)
                                ? strtolower(trim($state))
                                : null,
                        )
                        ->helperText(
                            '3–50 karakter: huruf, angka, titik, strip, atau underscore.',
                        ),

                    Forms\Components\TextInput::make('email')
                        ->label('Email Aktif')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->dehydrateStateUsing(
                            static fn (?string $state): ?string => filled($state)
                                ? strtolower(trim($state))
                                : null,
                        )
                        ->helperText(
                            'Dipakai untuk menerima tautan lupa kata sandi.',
                        ),

                    Forms\Components\TextInput::make('password')
                        ->label('Kata Sandi')
                        ->password()
                        ->revealable()
                        ->required(static fn (?Guru $record): bool => $record === null)
                        ->dehydrated(
                            static fn (?string $state): bool => filled($state),
                        )
                        ->minLength(8)
                        ->helperText(
                            'Kosongkan saat mengubah data jika kata sandi tidak ingin diganti.',
                        ),

                    Forms\Components\TextInput::make('no_telp')
                        ->label('Nomor Telepon')
                        ->tel()
                        ->maxLength(15),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama Guru')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.username')
                    ->label('Username')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('no_telp')
                    ->label('Telepon')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('can_input_nilai')
                    ->label('Bisa Input Nilai')
                    ->boolean()
                    ->tooltip('Aktif otomatis jika guru mempunyai penugasan pada menu Pengajar Kelas.'),
                Tables\Columns\TextColumn::make('jadwal_mengajars_count')
                    ->label('Kelas Diampu')
                    ->counts('jadwalMengajars')
                    ->badge(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->using(static fn (Guru $record): bool => app(GuruService::class)->delete($record)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGurus::route('/'),
            'create' => Pages\CreateGuru::route('/create'),
            'edit' => Pages\EditGuru::route('/{record}/edit'),
        ];
    }
}

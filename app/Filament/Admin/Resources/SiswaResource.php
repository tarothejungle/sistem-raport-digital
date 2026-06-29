<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\Concerns\AdminOnlyResource;
use App\Filament\Admin\Resources\SiswaResource\Pages;
use App\Models\Siswa;
use App\Services\SiswaService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SiswaResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = Siswa::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Siswa';

    protected static ?string $pluralModelLabel = 'Siswa';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data Peserta Didik')
                ->schema([
                    Forms\Components\TextInput::make('nisn')
                        ->label('NISN')
                        ->required()
                        ->maxLength(20)
                        ->rule('regex:/^\d{8,20}$/')
                        ->helperText('NISN hanya digunakan sebagai identitas peserta didik.')
                        ->unique(ignoreRecord: true),

                    Forms\Components\TextInput::make('nama_lengkap')
                        ->label('Nama Lengkap')
                        ->required()
                        ->maxLength(150),

                    Forms\Components\Select::make('kelas_id')
                        ->relationship('kelas', 'nama_kelas')
                        ->label('Kelas')
                        ->searchable()
                        ->preload()
                        ->required(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Akun Portal Siswa')
                ->description(
                    'Siswa masuk menggunakan username. Email digunakan untuk pemulihan kata sandi.',
                )
                ->schema([
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
                        ->required(
                            static fn (?Siswa $record): bool =>
                                $record === null || $record->user === null,
                        )
                        ->dehydrated(
                            static fn (?string $state): bool => filled($state),
                        )
                        ->minLength(8)
                        ->helperText(
                            'Kosongkan saat mengubah data jika kata sandi tidak ingin diganti.',
                        ),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nama_lengkap')
            ->columns([
                Tables\Columns\TextColumn::make('user.username')
                    ->label('Username')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('nisn')
                    ->label('NISN')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('nama_lengkap')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas')
                    ->badge()
                    ->sortable(),
                Tables\Columns\IconColumn::make('user_id')
                    ->label('Akun')
                    ->boolean()
                    ->state(static fn (Siswa $record): bool => $record->user_id !== null),
                Tables\Columns\IconColumn::make('can_view_nilai')
                    ->label('Akses Nilai')
                    ->boolean(),
                Tables\Columns\TextColumn::make('nilais_count')
                    ->label('Data Nilai')
                    ->counts('nilais')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kelas')
                    ->relationship('kelas', 'nama_kelas')
                    ->label('Kelas')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, Siswa $record): void {
                        if (! $record->nilais()->exists()) {
                            return;
                        }

                        Notification::make()
                            ->danger()
                            ->title('Siswa tidak dapat dihapus')
                            ->body('Hapus data nilai siswa terlebih dahulu.')
                            ->send();

                        $action->cancel();
                    })
                    ->using(static fn (Siswa $record): bool => app(SiswaService::class)->delete($record)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiswas::route('/'),
            'create' => Pages\CreateSiswa::route('/create'),
            'edit' => Pages\EditSiswa::route('/{record}/edit'),
        ];
    }
}

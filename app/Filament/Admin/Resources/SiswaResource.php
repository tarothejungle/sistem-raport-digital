<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\Concerns\AdminOnlyResource;
use App\Filament\Admin\Resources\SiswaResource\Pages\CreateSiswa;
use App\Filament\Admin\Resources\SiswaResource\Pages\EditSiswa;
use App\Filament\Admin\Resources\SiswaResource\Pages\ListSiswas;
use App\Models\Siswa;
use App\Services\SiswaService;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class SiswaResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = Siswa::class;

    protected static ?string $slug = 'siswa';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Data Siswa';

    protected static ?string $pluralModelLabel = 'Data Siswa';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->aktif();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Peserta Didik')
                ->schema([
                    TextInput::make('nisn')
                        ->label('NISN')
                        ->required()
                        ->maxLength(20)
                        ->rule('regex:/^\d{8,20}$/')
                        ->helperText('NISN hanya digunakan sebagai identitas peserta didik.')
                        ->unique(ignoreRecord: true),

                    TextInput::make('nama_lengkap')
                        ->label('Nama Lengkap')
                        ->required()
                        ->maxLength(150),

                    Select::make('jenis_kelamin')
                        ->label('Jenis Kelamin')
                        ->options([
                            'L' => 'Laki-laki',
                            'P' => 'Perempuan',
                        ])
                        ->native(true)
                        ->required(),

                    Select::make('kelas_id')
                        ->relationship('kelas', 'nama_kelas')
                        ->label('Kelas')
                        ->searchable()
                        ->preload()
                        ->required(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make('Akun Portal Siswa')
                ->description(
                    'Siswa masuk menggunakan username. Email digunakan untuk pemulihan kata sandi.',
                )
                ->schema([
                    TextInput::make('username')
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
                            '3-50 karakter: huruf, angka, titik, strip, atau underscore.',
                        ),

                    TextInput::make('email')
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

                    TextInput::make('password')
                        ->label('Kata Sandi')
                        ->password()
                        ->revealable()
                        ->required(
                            static fn (?Siswa $record): bool => $record === null || $record->user === null,
                        )
                        ->dehydrated(
                            static fn (?string $state): bool => filled($state),
                        )
                        ->minLength(8)
                        ->helperText(
                            'Kosongkan saat mengubah data jika kata sandi tidak ingin diganti.',
                        ),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'raport-mobile-full-search-table'])
            ->defaultSort('nama_lengkap')
            ->columns([
                TextColumn::make('user.username')
                    ->label('Username')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('nisn')
                    ->label('NISN')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('nama_lengkap')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->formatStateUsing(static fn (?string $state): string => match ($state) {
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                        default => '-',
                    })
                    ->badge(),
                TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas')
                    ->badge()
                    ->sortable(),
                IconColumn::make('user_id')
                    ->label('Akun')
                    ->boolean()
                    ->state(static fn (Siswa $record): bool => $record->user_id !== null),
                IconColumn::make('can_view_nilai')
                    ->label('Akses Nilai')
                    ->boolean(),
                TextColumn::make('nilais_count')
                    ->label('Data Nilai')
                    ->counts('nilais')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('kelas')
                    ->relationship('kelas', 'nama_kelas')
                    ->label('Kelas')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, Siswa $record): void {
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
            ])
            ->toolbarActions([
                BulkAction::make('deleteSelected')
                    ->label('Hapus Terpilih')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus siswa terpilih?')
                    ->modalDescription('Siswa yang masih memiliki data nilai tidak akan dihapus.')
                    ->modalSubmitActionLabel('Ya, hapus')
                    ->action(function ($records): void {
                        $deleted = 0;
                        $blocked = 0;

                        foreach ($records as $record) {
                            try {
                                if (app(SiswaService::class)->delete($record)) {
                                    $deleted++;
                                }
                            } catch (ValidationException) {
                                $blocked++;
                            }
                        }

                        $notification = Notification::make()
                            ->title($blocked > 0 ? 'Sebagian siswa tidak dapat dihapus' : 'Siswa terpilih berhasil dihapus')
                            ->body("{$deleted} siswa dihapus. {$blocked} siswa dilewati karena masih memiliki data nilai.");

                        ($blocked > 0 ? $notification->warning() : $notification->success())->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSiswas::route('/'),
            'create' => CreateSiswa::route('/create'),
            'edit' => EditSiswa::route('/{record}/edit'),
        ];
    }
}

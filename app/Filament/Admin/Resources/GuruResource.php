<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\Concerns\AdminOnlyResource;
use App\Filament\Admin\Resources\GuruResource\Pages\CreateGuru;
use App\Filament\Admin\Resources\GuruResource\Pages\EditGuru;
use App\Filament\Admin\Resources\GuruResource\Pages\ListGurus;
use App\Models\Guru;
use App\Services\GuruService;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class GuruResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = Guru::class;

    protected static ?string $slug = 'guru';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Data Guru';

    protected static ?string $pluralModelLabel = 'Data Guru';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Guru dan Akun Masuk')
                ->description(
                    'Guru masuk menggunakan username. Email digunakan untuk pemulihan kata sandi.',
                )
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Lengkap')
                        ->required()
                        ->maxLength(150),

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
                        ->required(static fn (?Guru $record): bool => $record === null)
                        ->dehydrated(
                            static fn (?string $state): bool => filled($state),
                        )
                        ->minLength(8)
                        ->helperText(
                            'Kosongkan saat mengubah data jika kata sandi tidak ingin diganti.',
                        ),

                    TextInput::make('no_telp')
                        ->label('Nomor Telepon')
                        ->tel()
                        ->maxLength(15),

                    Select::make('jenis_kelamin')
                        ->label('Jenis Kelamin')
                        ->options([
                            'L' => 'Laki-laki',
                            'P' => 'Perempuan',
                        ])
                        ->native(true)
                        ->required(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make('Data Diri Guru')
                ->description('Data ini ditampilkan pada dashboard guru.')
                ->schema([
                    TextInput::make('tempat_lahir')
                        ->label('Tempat Lahir')
                        ->maxLength(100),

                    DatePicker::make('tanggal_lahir')
                        ->label('Tanggal Lahir')
                        ->native(false),

                    TextInput::make('pendidikan_terakhir')
                        ->label('Pendidikan Terakhir')
                        ->maxLength(100)
                        ->placeholder('Contoh: S1 Pendidikan Agama Islam'),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'raport-mobile-full-search-table'])
            ->defaultSort('user.name', 'asc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Nama Guru')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.username')
                    ->label('Username')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->formatStateUsing(static fn (?string $state): string => match ($state) {
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                        default => '-',
                    })
                    ->badge(),
                TextColumn::make('no_telp')
                    ->label('Telepon')
                    ->toggleable(),
                IconColumn::make('can_input_nilai')
                    ->label('Bisa Input Nilai')
                    ->boolean()
                    ->tooltip('Aktif otomatis jika guru mempunyai penugasan pada menu Pengajar Kelas.'),
                TextColumn::make('jadwal_mengajars_count')
                    ->label('Kelas Diampu')
                    ->counts('jadwalMengajars')
                    ->badge(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->using(static fn (Guru $record): bool => app(GuruService::class)->delete($record)),
            ])
            ->toolbarActions([
                BulkAction::make('deleteSelected')
                    ->label('Hapus Terpilih')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus guru terpilih?')
                    ->modalDescription('Guru yang masih memiliki penugasan mengajar tidak akan dihapus.')
                    ->modalSubmitActionLabel('Ya, hapus')
                    ->action(function ($records): void {
                        $deleted = 0;
                        $blocked = 0;

                        foreach ($records as $record) {
                            try {
                                if (app(GuruService::class)->delete($record)) {
                                    $deleted++;
                                }
                            } catch (ValidationException) {
                                $blocked++;
                            }
                        }

                        $notification = Notification::make()
                            ->title($blocked > 0 ? 'Sebagian guru tidak dapat dihapus' : 'Guru terpilih berhasil dihapus')
                            ->body("{$deleted} guru dihapus. {$blocked} guru dilewati karena masih memiliki penugasan mengajar.");

                        ($blocked > 0 ? $notification->warning() : $notification->success())->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGurus::route('/'),
            'create' => CreateGuru::route('/create'),
            'edit' => EditGuru::route('/{record}/edit'),
        ];
    }
}

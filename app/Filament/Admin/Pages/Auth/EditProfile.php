<?php

namespace App\Filament\Admin\Pages\Auth;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class EditProfile extends \Filament\Auth\Pages\EditProfile
{
    public static function getLabel(): string
    {
        return 'Profil Saya';
    }

    public function getHeading(): string
    {
        return 'Profil Saya';
    }

    public function getSubheading(): ?string
    {
        return 'Kelola identitas akun dan foto profil Anda.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Foto Profil')
                ->description('Gunakan foto wajah yang jelas agar akun mudah dikenali.')
                ->schema([
                    FileUpload::make('avatar_path')
                        ->label('Upload Foto')
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->avatar()
                        ->imageEditor()
                        ->imageEditorAspectRatios([
                            '1:1',
                        ])
                        ->disk('public')
                        ->directory('avatars')
                        ->visibility('public')
                        ->maxSize(2048)
                        ->helperText('Format JPG, JPEG, PNG, atau WEBP. Maksimal 2 MB.')
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            Section::make('Informasi Akun')
                ->description('Perbarui nama dan email yang digunakan pada akun ini.')
                ->schema([
                    $this->getNameFormComponent()
                        ->label('Nama Lengkap')
                        ->prefixIcon('heroicon-o-user'),

                    $this->getEmailFormComponent()
                        ->label('Email')
                        ->prefixIcon('heroicon-o-envelope'),

                    Placeholder::make('role')
                        ->label('Peran Akun')
                        ->content(fn (): string => $this->roleLabel())
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make('Data Guru')
                ->description('Lengkapi data diri yang ditampilkan pada dashboard guru.')
                ->schema([
                    TextInput::make('guru.no_telp')
                        ->label('Nomor Handphone')
                        ->tel()
                        ->maxLength(15),

                    TextInput::make('guru.tempat_lahir')
                        ->label('Tempat Lahir')
                        ->maxLength(100),

                    DatePicker::make('guru.tanggal_lahir')
                        ->label('Tanggal Lahir')
                        ->native(false),

                    TextInput::make('guru.pendidikan_terakhir')
                        ->label('Pendidikan Terakhir')
                        ->maxLength(100)
                        ->placeholder('Contoh: S1 Pendidikan Agama Islam'),
                ])
                ->columns(2)
                ->columnSpanFull()
                ->visible(fn (): bool => auth()->user()?->isGuru() ?? false),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = $this->getUser();

        if ($user instanceof User && $user->isGuru()) {
            $user->loadMissing('guru');
            $data['guru'] = $user->guru?->only([
                'no_telp',
                'tempat_lahir',
                'tanggal_lahir',
                'pendidikan_terakhir',
            ]) ?? [];
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $guruData = Arr::pull($data, 'guru', []);

        $record->update($data);

        if ($record instanceof User && $record->isGuru() && $record->guru !== null) {
            $record->guru->update(Arr::only($guruData, [
                'no_telp',
                'tempat_lahir',
                'tanggal_lahir',
                'pendidikan_terakhir',
            ]));
        }

        return $record;
    }

    protected function afterSave(): void
    {
        $user = $this->getUser()->refresh();

        $this->dispatch(
            'profile-avatar-updated',
            url: $user instanceof User ? $user->getFilamentAvatarUrl() : null,
        );
    }

    public function deleteAvatar(): void
    {
        $user = $this->getUser();

        if (! $user instanceof User || blank($user->avatar_path)) {
            return;
        }

        Storage::disk('public')->delete($user->avatar_path);

        $user->update([
            'avatar_path' => null,
        ]);

        $this->data['avatar_path'] = null;
    }

    /**
     * Keep form labels above their controls in the profile page.
     */
    public function defaultForm(Schema $schema): Schema
    {
        return parent::defaultForm($schema)
            ->inlineLabel(false);
    }

    private function roleLabel(): string
    {
        return match (auth()->user()?->role) {
            'admin' => 'Administrator',
            'guru' => 'Guru',
            'siswa' => 'Siswa',
            default => 'Pengguna',
        };
    }
}

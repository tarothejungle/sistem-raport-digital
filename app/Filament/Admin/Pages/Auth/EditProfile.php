<?php

namespace App\Filament\Admin\Pages\Auth;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

class EditProfile extends BaseEditProfile
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

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Foto Profil')
                ->description('Gunakan foto wajah yang jelas agar akun mudah dikenali.')
                ->schema([
                    Forms\Components\FileUpload::make('avatar_path')
                        ->label('Foto Profil')
                        ->image()
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
                ]),

            Forms\Components\Section::make('Informasi Akun')
                ->description('Perbarui nama dan email yang digunakan pada akun ini.')
                ->schema([
                    $this->getNameFormComponent()
                        ->label('Nama Lengkap')
                        ->prefixIcon('heroicon-o-user'),

                    $this->getEmailFormComponent()
                        ->label('Email')
                        ->prefixIcon('heroicon-o-envelope'),

                    Forms\Components\Placeholder::make('role')
                        ->label('Peran Akun')
                        ->content(fn (): string => $this->roleLabel())
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    /**
     * Memaksa label field berada di atas input,
     * bukan sejajar jauh di sisi kiri.
     *
     * @return array<string, Form>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->operation('edit')
                    ->model($this->getUser())
                    ->statePath('data')
                    ->inlineLabel(false),
            ),
        ];
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
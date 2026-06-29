<?php

namespace App\Filament\Admin\Pages\Auth;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Support\Facades\Route;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

class ChangePassword extends BaseEditProfile
{
    protected static ?string $slug = 'ganti-kata-sandi';

    protected static bool $shouldRegisterNavigation = false;

    public static function getLabel(): string
    {
        return 'Ganti Kata Sandi';
    }

    public static function getRelativeRouteName(): string
    {
        return 'change-password';
    }

    public static function registerRoutes(Panel $panel): void
    {
        Route::name('pages.')->group(function () use ($panel): void {
            static::routes($panel);
        });
    }

    public static function getRouteName(?string $panel = null): string
    {
        $panel = $panel
            ? Filament::getPanel($panel)
            : Filament::getCurrentPanel();

        return $panel->generateRouteName(
            'pages.'.static::getRelativeRouteName(),
        );
    }

    public function getHeading(): string
    {
        return 'Ganti Kata Sandi';
    }

    public function getSubheading(): ?string
    {
        return 'Masukkan kata sandi saat ini, lalu buat kata sandi baru untuk akun Anda.';
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Keamanan Akun')
                ->description('Gunakan kata sandi yang kuat dan jangan membagikannya kepada orang lain.')
                ->schema([
                    Forms\Components\TextInput::make('current_password')
                        ->label('Kata Sandi Saat Ini')
                        ->password()
                        ->revealable(filament()->arePasswordsRevealable())
                        ->required()
                        ->rule('current_password')
                        ->dehydrated(false)
                        ->autocomplete('current-password')
                        ->prefixIcon('heroicon-o-lock-closed'),

                    $this->getPasswordFormComponent()
                        ->label('Kata Sandi Baru')
                        ->required()
                        ->prefixIcon('heroicon-o-key'),

                    $this->getPasswordConfirmationFormComponent()
                        ->label('Konfirmasi Kata Sandi Baru')
                        ->prefixIcon('heroicon-o-shield-check'),
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

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Simpan Kata Sandi')
            ->icon('heroicon-o-check');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Kembali');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Kata sandi berhasil diperbarui.';
    }
}
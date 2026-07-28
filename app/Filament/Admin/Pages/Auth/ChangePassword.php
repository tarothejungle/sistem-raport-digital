<?php

namespace App\Filament\Admin\Pages\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Pages\PageConfiguration;
use Filament\Panel;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Route;

class ChangePassword extends BaseEditProfile
{
    protected static ?string $slug = 'ganti-kata-sandi';

    protected static bool $shouldRegisterNavigation = false;

    public static function getLabel(): string
    {
        return 'Ganti Kata Sandi';
    }

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'change-password';
    }

    public static function registerRoutes(Panel $panel, ?PageConfiguration $configuration = null): void
    {
        Route::name('pages.')->group(function () use ($panel, $configuration): void {
            static::routes($panel, $configuration);
        });
    }

    public static function getRouteName(?Panel $panel = null): string
    {
        $panel ??= Filament::getCurrentOrDefaultPanel();

        return $panel->generateRouteName(
            'pages.'.static::getRelativeRouteName($panel),
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

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Keamanan Akun')
                ->description('Gunakan kata sandi yang kuat dan jangan membagikannya kepada orang lain.')
                ->schema([
                    TextInput::make('current_password')
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
     * Keep form labels above their controls in the password page.
     */
    public function defaultForm(Schema $schema): Schema
    {
        return parent::defaultForm($schema)
            ->inlineLabel(false);
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

<?php

namespace App\Filament\Pages\Auth\PasswordReset;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Log;
use MuazzamBuilds\FilamentTurnstile\Concerns\InteractsWithTurnstile;

class RequestPasswordReset extends \Filament\Auth\Pages\PasswordReset\RequestPasswordReset
{
    use InteractsWithTurnstile;

    protected string $view = 'filament.pages.auth.password-reset.request-password-reset';

    protected Width|string|null $maxWidth = '6xl';

    public function hasLogo(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getTurnstileFormComponent('password-reset')
                    ->size('flexible')
                    ->theme('auto')
                    ->language('id')
                    ->extraFieldWrapperAttributes([
                        'class' => 'srd-login-turnstile',
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('EMAIL')
            ->placeholder('Masukkan email terdaftar')
            ->prefixIcon('heroicon-m-envelope')
            ->email()
            ->required()
            ->autocomplete('email')
            ->autofocus()
            ->extraFieldWrapperAttributes([
                'class' => 'srd-login-field srd-reset-email',
            ]);
    }

    protected function getRequestFormAction(): Action
    {
        return parent::getRequestFormAction()
            ->label('Kirim Tautan Reset')
            ->icon(null)
            ->extraAttributes([
                'class' => 'srd-login-submit',
            ]);
    }

    public function request(): void
    {
        parent::request();

        $this->dispatchTurnstileReset();
    }

    protected function getFailureNotification(string $status): ?Notification
    {
        Log::notice('Password reset requested for an unknown or ineligible account.');

        return $this->getGenericSentNotification();
    }

    protected function getSentNotification(string $status): ?Notification
    {
        return $this->getGenericSentNotification();
    }

    private function getGenericSentNotification(): Notification
    {
        return Notification::make()
            ->title('Permintaan reset diproses')
            ->body('Jika email terdaftar, tautan reset kata sandi akan dikirim.')
            ->success();
    }

    public function getTitle(): string
    {
        return 'Reset Kata Sandi - Sistem Rapor Digital';
    }

    public function getHeading(): string
    {
        return 'Reset Kata Sandi';
    }

    public function getSubheading(): null
    {
        return null;
    }
}

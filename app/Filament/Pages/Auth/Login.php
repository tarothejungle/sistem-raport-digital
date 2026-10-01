<?php

namespace App\Filament\Pages\Auth;

use App\Services\LoginIdentifierResolver;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use MuazzamBuilds\FilamentTurnstile\Concerns\InteractsWithTurnstile;

class Login extends \Filament\Auth\Pages\Login
{
    use InteractsWithTurnstile;

    protected string $view = 'filament.pages.auth.login';

    protected Width|string|null $maxWidth = '6xl';

    public function hasLogo(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        $passwordResetUrl = Filament::getCurrentOrDefaultPanel()
            ->getRequestPasswordResetUrl() ?? '#';

        return $schema
            ->components([
                TextInput::make('login')
                    ->label('USERNAME')
                    ->validationAttribute('Username')
                    ->placeholder('Masukkan username')
                    ->prefixIcon('heroicon-m-identification')
                    ->extraFieldWrapperAttributes([
                        'class' => 'srd-login-field srd-login-field--username',
                    ])
                    ->required()
                    ->autocomplete('username')
                    ->autofocus(),

                $this->getPasswordFormComponent(),

                Grid::make()
                    ->columns(2)
                    ->extraAttributes([
                        'class' => 'srd-login-options',
                    ])
                    ->schema([
                        $this->getRememberFormComponent()
                            ->label('INGAT SAYA')
                            ->columnSpan(1)
                            ->extraAttributes([
                                'class' => 'srd-login-remember',
                            ]),

                        View::make('filament.admin.components.login-reset-link')
                            ->viewData([
                                'url' => $passwordResetUrl,
                            ])
                            ->columnSpan(1)
                            ->extraAttributes([
                                'class' => 'srd-login-reset-cell',
                            ]),
                    ]),

                $this->getTurnstileFormComponent('login')
                    ->label('Verifikasi keamanan')
                    ->validationAttribute('verifikasi keamanan')
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

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->hint(null)
            ->label('KATA SANDI')
            ->validationAttribute('Kata sandi')
            ->placeholder('Masukkan kata sandi')
            ->prefixIcon('heroicon-m-lock-closed')
            ->password()
            ->revealable()
            ->extraFieldWrapperAttributes([
                'class' => 'srd-login-field srd-login-field--password',
            ])
            ->extraAttributes([
                'autocomplete' => 'current-password',
            ]);
    }

    public function getTitle(): string
    {
        return 'Masuk - Sistem Rapor Digital';
    }

    public function getHeading(): string
    {
        return 'Masuk ke Portal';
    }

    public function getSubheading(): ?string
    {
        return 'SISTEM RAPOR DIGITAL';
    }

    protected function getAuthenticateFormAction(): Action
    {
        return parent::getAuthenticateFormAction()
            ->label('Login ke Sistem')
            ->icon(null)
            ->extraAttributes([
                'class' => 'srd-login-submit',
            ]);
    }

    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();
        $throttleKey = $this->throttleKey((string) $data['login']);
        $ipThrottleKey = $this->ipThrottleKey();

        if (
            RateLimiter::tooManyAttempts($throttleKey, 5)
            || RateLimiter::tooManyAttempts($ipThrottleKey, 20)
        ) {
            $this->dispatchTurnstileReset();

            throw ValidationException::withMessages([
                'data.login' => sprintf(
                    'Terlalu banyak percobaan masuk. Coba lagi dalam %d detik.',
                    max(
                        RateLimiter::availableIn($throttleKey),
                        RateLimiter::availableIn($ipThrottleKey),
                    ),
                ),
            ]);
        }

        $user = app(LoginIdentifierResolver::class)->resolve((string) $data['login']);

        if ($user === null || ! Hash::check((string) $data['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            RateLimiter::hit($ipThrottleKey, 60);
            $this->dispatchTurnstileReset();

            throw ValidationException::withMessages([
                'data.login' => 'Data masuk atau kata sandi tidak sesuai.',
            ]);
        }

        if (! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel())) {
            RateLimiter::hit($throttleKey, 60);
            RateLimiter::hit($ipThrottleKey, 60);
            $this->dispatchTurnstileReset();

            throw ValidationException::withMessages([
                'data.login' => 'Data masuk atau kata sandi tidak sesuai.',
            ]);
        }

        Filament::auth()->login($user, (bool) ($data['remember'] ?? false));
        request()->session()->regenerate();
        RateLimiter::clear($throttleKey);
        RateLimiter::clear($ipThrottleKey);
        $this->dispatch('gamified-login-success');

        return app(LoginResponse::class);
    }

    private function throttleKey(string $login): string
    {
        return sha1(strtolower(trim($login)).'|'.request()->ip());
    }

    private function ipThrottleKey(): string
    {
        return 'login-ip:'.sha1((string) request()->ip());
    }
}

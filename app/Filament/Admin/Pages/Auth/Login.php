<?php

namespace App\Filament\Admin\Pages\Auth;

use App\Services\LoginIdentifierResolver;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Actions\Action;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Forms\Components\Component;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function form(Form $form): Form
    {
        $passwordResetUrl = Filament::getCurrentPanel()
            ->getRequestPasswordResetUrl() ?? '#';

        return $form
            ->schema([
                Forms\Components\TextInput::make('login')
                    ->label('Username')
                    ->placeholder('Username')
                    ->prefixIcon('heroicon-m-identification')
                    ->required()
                    ->autocomplete('username')
                    ->autofocus(),

                $this->getPasswordFormComponent(),

                Forms\Components\Grid::make()
                    ->columns(2)
                    ->extraAttributes([
                        'style' => 'grid-template-columns: minmax(0, 1fr) auto; align-items: center;',
                    ])
                    ->schema([
                        $this->getRememberFormComponent()
                            ->label('Ingat Saya')
                            ->columnSpan(1)
                            ->extraAttributes([
                                'style' => 'white-space: nowrap;',
                            ]),

                        Forms\Components\Placeholder::make('password_reset_link')
                            ->hiddenLabel()
                            ->columnSpan(1)
                            ->extraAttributes([
                                'style' => 'justify-self: end; white-space: nowrap;',
                            ])
                            ->content(
                                new HtmlString(
                                    sprintf(
                                        '<a
                                            href="%s"
                                            style="white-space: nowrap; font-size: 0.875rem; font-weight: 500; color: #2563eb;"
                                        >
                                            Lupa kata sandi?
                                        </a>',
                                        e($passwordResetUrl),
                                    ),
                                ),
                            ),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->hint(null)
            ->label('Password')
            ->prefixIcon('heroicon-m-lock-closed');
    }

    public function getTitle(): string
    {
        return 'Login';
    }

    public function getHeading(): string
    {
        return 'Sistem Raport Digital';
    }

    public function getSubheading(): ?string
    {
        return 'Gunakan akun yang telah diberikan.';
    }

    protected function getAuthenticateFormAction(): Action
    {
        return parent::getAuthenticateFormAction()
            ->label('Masuk ke Dashboard')
            ->icon('heroicon-m-arrow-right');
    }

    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();
        $throttleKey = $this->throttleKey((string) $data['login']);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'data.login' => sprintf(
                    'Terlalu banyak percobaan masuk. Coba lagi dalam %d detik.',
                    RateLimiter::availableIn($throttleKey),
                ),
            ]);
        }

        $user = app(LoginIdentifierResolver::class)->resolve((string) $data['login']);

        if ($user === null || ! Hash::check((string) $data['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'data.login' => 'Data masuk atau kata sandi tidak sesuai.',
            ]);
        }

        if (! $user->canAccessPanel(Filament::getCurrentPanel())) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'data.login' => 'Akun ini belum memiliki akses ke portal raport.',
            ]);
        }

        Filament::auth()->login($user, (bool) ($data['remember'] ?? false));
        request()->session()->regenerate();
        RateLimiter::clear($throttleKey);

        return app(LoginResponse::class);
    }

    private function throttleKey(string $login): string
    {
        return sha1(strtolower(trim($login)).'|'.request()->ip());
    }
}

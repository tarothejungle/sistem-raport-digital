<x-filament-panels::page.simple heading="" subheading="">
    <div class="srd-game-login" x-data="gamifiedLogin">
        <canvas
            x-ref="confetti"
            class="srd-game-login__confetti"
            aria-hidden="true"
        ></canvas>

        <div class="srd-game-login__orb srd-game-login__orb--one" aria-hidden="true"></div>
        <div class="srd-game-login__orb srd-game-login__orb--two" aria-hidden="true"></div>
        <div class="srd-game-login__grid" aria-hidden="true"></div>

        <div class="srd-game-login__theme" aria-label="Pilih tema tampilan">
            <x-filament-panels::theme-switcher />
        </div>

        <main
            class="srd-game-card srd-game-card--reset"
            @pointermove="tilt($event)"
            @pointerleave="resetTilt($event)"
            aria-labelledby="reset-heading"
        >
            <div class="srd-game-card__shine" aria-hidden="true"></div>

            <header class="srd-game-card__header">
                <a href="{{ filament()->getLoginUrl() }}" class="srd-game-card__badge" aria-label="Kembali ke halaman login">
                    <img src="{{ asset('logo/logo-rapor-dark.png') }}" alt="" class="srd-theme-logo srd-theme-logo--light">
                    <img src="{{ asset('logo/logo-rapor-light.png') }}" alt="" class="srd-theme-logo srd-theme-logo--dark">
                </a>

                <h1 id="reset-heading">Reset Kata Sandi</h1>
                <p>SISTEM RAPOR DIGITAL</p>
                <span>Masukkan email terdaftar. Tautan reset akan dikirim ke kotak masukmu.</span>
            </header>

            <div class="srd-game-card__form">
                {{ $this->content }}
            </div>

            <a href="{{ filament()->getLoginUrl() }}" class="srd-game-card__back">
                <x-filament::icon icon="heroicon-m-arrow-left" aria-hidden="true" />
                Kembali ke Login
            </a>

            <div class="srd-game-card__status" aria-hidden="true">
                <span></span>
                Pemulihan akun dilindungi Turnstile
            </div>
        </main>
    </div>

    @include('filament.pages.auth.password-reset.request-password-reset-styles')
</x-filament-panels::page.simple>

<x-filament-panels::page.simple heading="" subheading="">
    @php
        $illustrationLightPath = 'logo/undraw_educato_light.svg';
        $illustrationDarkPath = 'logo/undraw_educato_dark.svg';
        $symbolLightPath = 'logo/logo-tanpa-teks-light.svg';
        $symbolDarkPath = 'logo/logo-tanpa-teks-dark.svg';
        $illustrationLightVersion = file_exists(public_path($illustrationLightPath)) ? filemtime(public_path($illustrationLightPath)) : time();
        $illustrationDarkVersion = file_exists(public_path($illustrationDarkPath)) ? filemtime(public_path($illustrationDarkPath)) : time();
        $symbolLightVersion = file_exists(public_path($symbolLightPath)) ? filemtime(public_path($symbolLightPath)) : time();
        $symbolDarkVersion = file_exists(public_path($symbolDarkPath)) ? filemtime(public_path($symbolDarkPath)) : time();
    @endphp

    <div class="srd-login-shell">
        {{-- Art panel --}}
        <section class="srd-login-panel srd-login-panel--art" aria-hidden="true">
            <div class="srd-login-illustration">
                <img
                    src="{{ asset($illustrationLightPath) }}?v={{ $illustrationLightVersion }}"
                    alt=""
                    loading="eager"
                    decoding="async"
                    class="srd-login-illustration__image srd-login-illustration__image--light"
                >
                <img
                    src="{{ asset($illustrationDarkPath) }}?v={{ $illustrationDarkVersion }}"
                    alt=""
                    loading="eager"
                    decoding="async"
                    class="srd-login-illustration__image srd-login-illustration__image--dark"
                >
            </div>
        </section>

        {{-- Form panel --}}
        <section class="srd-login-panel srd-login-panel--form" aria-label="Form masuk">
            <div class="srd-login-theme">
                <x-filament-panels::theme-switcher/>
            </div>

            <div class="srd-login-form">
                <div class="srd-login-form-brand">
                    <img
                        src="{{ asset($symbolLightPath) }}?v={{ $symbolLightVersion }}"
                        alt="Sistem Rapor Digital"
                        class="srd-login-form-brand__image srd-login-form-brand__image--light"
                        loading="eager"
                        decoding="async"
                    >
                    <img
                        src="{{ asset($symbolDarkPath) }}?v={{ $symbolDarkVersion }}"
                        alt=""
                        aria-hidden="true"
                        class="srd-login-form-brand__image srd-login-form-brand__image--dark"
                        loading="eager"
                        decoding="async"
                    >
                </div>

                <h1 class="srd-login-form-heading">{{ $this->getHeading() }}</h1>

                <div class="srd-login-fields">
                    {{ $this->content }}
                </div>
            </div>
        </section>
    </div>

</x-filament-panels::page.simple>

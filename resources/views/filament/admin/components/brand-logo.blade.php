@php
    $label = $label ?? 'Sistem Rapor Digital';
    $lightLogoPath = 'logo/logo-tema-light.svg';
    $darkLogoPath = 'logo/logo-tema-dark.svg';
    $lightLogoVersion = filemtime(public_path($lightLogoPath));
    $darkLogoVersion = filemtime(public_path($darkLogoPath));
@endphp

<span @class(['raport-brand-logo', $class ?? null])>
    <span class="raport-brand-logo__mark">
        <img
            class="raport-brand-logo__image raport-brand-logo__image--light"
            src="{{ asset($lightLogoPath) }}?v={{ $lightLogoVersion }}"
            alt="{{ $label }}"
        >
        <img
            class="raport-brand-logo__image raport-brand-logo__image--dark"
            src="{{ asset($darkLogoPath) }}?v={{ $darkLogoVersion }}"
            alt=""
            aria-hidden="true"
        >
    </span>
</span>

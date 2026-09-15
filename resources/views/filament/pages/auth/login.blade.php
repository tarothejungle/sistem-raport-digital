<x-filament-panels::page.simple heading="" subheading="">
    <div
        class="srd-game-login"
        x-data="gamifiedLogin"
    >
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
            class="srd-game-card"
            @pointermove="tilt($event)"
            @pointerleave="resetTilt($event)"
            aria-labelledby="login-heading"
        >
            <div class="srd-game-card__shine" aria-hidden="true"></div>

            <header class="srd-game-card__header">
                <a href="{{ url('/') }}" class="srd-game-card__badge" aria-label="Sistem Rapor Digital - Beranda">
                    <img src="{{ asset('logo/logo-rapor-dark.png') }}" alt="" class="srd-theme-logo srd-theme-logo--light">
                    <img src="{{ asset('logo/logo-rapor-light.png') }}" alt="" class="srd-theme-logo srd-theme-logo--dark">
                </a>

                <h1 id="login-heading">Masuk ke Portal</h1>
                <p>SISTEM RAPOR DIGITAL</p>
            </header>

            <div class="srd-game-card__form">
                {{ $this->content }}
            </div>

            <div class="srd-game-card__status" aria-hidden="true">
                <span></span>
                Portal aman dan siap digunakan
            </div>
        </main>
    </div>

    <style>
        body.fi-body:has(.srd-game-login),
        .fi-simple-layout:has(.srd-game-login) {
            background: #eaf2ff !important;
        }

        html.dark body.fi-body:has(.srd-game-login),
        html.dark .fi-simple-layout:has(.srd-game-login) {
            background: #020617 !important;
        }

        .fi-simple-layout:has(.srd-game-login)::before,
        .fi-simple-main:has(.srd-game-login)::before,
        .fi-simple-page:has(.srd-game-login) .fi-simple-header {
            display: none !important;
        }

        .fi-simple-layout:has(.srd-game-login) .fi-simple-main-ctn {
            align-items: stretch;
            min-height: 100svh;
            padding: 0 !important;
        }

        html:not(.dark) .fi-simple-main:has(.srd-game-login),
        html.dark .fi-simple-main:has(.srd-game-login) {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
        }

        .fi-simple-page:has(.srd-game-login),
        .fi-simple-page:has(.srd-game-login) .fi-simple-page-content {
            width: 100% !important;
            min-height: 100svh;
            padding: 0 !important;
        }

        .srd-game-login {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100svh;
            overflow: hidden;
            padding: 5rem 1rem 2rem;
            color: #0f172a;
            isolation: isolate;
        }

        .srd-game-login__grid {
            position: absolute;
            z-index: -3;
            inset: 0;
            opacity: 0.14;
            background-image:
                linear-gradient(rgba(37, 99, 235, 0.25) 1px, transparent 1px),
                linear-gradient(90deg, rgba(37, 99, 235, 0.25) 1px, transparent 1px);
            background-size: 42px 42px;
            mask-image: radial-gradient(circle at center, black, transparent 78%);
            pointer-events: none;
        }

        html.dark .srd-game-login__grid {
            opacity: 0.12;
            background-image:
                linear-gradient(rgba(96, 165, 250, 0.3) 1px, transparent 1px),
                linear-gradient(90deg, rgba(96, 165, 250, 0.3) 1px, transparent 1px);
        }

        .srd-game-login__orb {
            position: absolute;
            z-index: -2;
            width: 26rem;
            height: 26rem;
            border-radius: 999px;
            filter: blur(90px);
            pointer-events: none;
            animation: srd-orb-float 9s ease-in-out infinite alternate;
        }

        .srd-game-login__orb--one {
            top: -9rem;
            left: -6rem;
            background: rgba(37, 99, 235, 0.28);
        }

        .srd-game-login__orb--two {
            right: -8rem;
            bottom: -10rem;
            background: rgba(34, 211, 238, 0.22);
            animation-delay: -4s;
        }

        .srd-game-login__confetti {
            position: fixed;
            z-index: 50;
            inset: 0;
            pointer-events: none;
        }

        .srd-game-login__theme {
            position: fixed;
            z-index: 30;
            top: 1.25rem;
            right: 1.25rem;
        }

        .srd-game-login__theme .fi-theme-switcher {
            gap: 0.15rem;
            padding: 0.22rem;
            border: 1px solid rgba(148, 163, 184, 0.28) !important;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.76) !important;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
            backdrop-filter: blur(16px);
        }

        html.dark .srd-game-login__theme .fi-theme-switcher {
            border-color: rgba(255, 255, 255, 0.12) !important;
            background: rgba(15, 23, 42, 0.72) !important;
        }

        .srd-game-login__theme .fi-theme-switcher-btn {
            width: 2rem !important;
            height: 2rem !important;
            min-width: 2rem !important;
            min-height: 2rem !important;
            border-radius: 999px !important;
            color: #64748b !important;
        }

        .srd-game-login__theme .fi-theme-switcher-btn.fi-active {
            background: rgba(37, 99, 235, 0.14) !important;
            color: #2563eb !important;
        }

        .srd-game-card {
            --card-rx: 0deg;
            --card-ry: 0deg;
            position: relative;
            z-index: 10;
            width: min(100%, 29rem);
            overflow: hidden;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.68);
            border-radius: 1.5rem;
            background: rgba(255, 255, 255, 0.8);
            box-shadow: 0 30px 90px rgba(30, 64, 175, 0.2), 0 8px 24px rgba(15, 23, 42, 0.08);
            opacity: 1;
            transform: perspective(1000px) rotateX(var(--card-rx)) rotateY(var(--card-ry));
            transform-style: preserve-3d;
            backdrop-filter: blur(24px);
            animation: srd-card-enter 420ms cubic-bezier(.2, .8, .2, 1) both;
            transition: opacity 420ms ease, transform 420ms cubic-bezier(.2, .8, .2, 1), box-shadow 180ms ease;
            will-change: transform;
        }

        .srd-game-card:hover {
            box-shadow: 0 34px 100px rgba(30, 64, 175, 0.28), 0 10px 28px rgba(15, 23, 42, 0.1);
            transform: perspective(1000px) translateY(-3px) scale(1.01) rotateX(var(--card-rx)) rotateY(var(--card-ry));
        }

        html.dark .srd-game-card {
            border-color: rgba(148, 163, 184, 0.18);
            background: rgba(15, 23, 42, 0.8);
            box-shadow: 0 32px 100px rgba(0, 0, 0, 0.58), 0 0 45px rgba(37, 99, 235, 0.2);
        }

        .srd-game-card__shine {
            position: absolute;
            top: -8rem;
            left: -5rem;
            width: 15rem;
            height: 15rem;
            border-radius: 999px;
            background: rgba(96, 165, 250, 0.14);
            filter: blur(35px);
            pointer-events: none;
        }

        .srd-game-card__header {
            position: relative;
            text-align: center;
        }

        .srd-game-card__badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3.65rem;
            height: 4rem;
            margin-bottom: 0.9rem;
            color: #0f172a !important;
            filter: drop-shadow(0 10px 16px rgba(15, 23, 42, 0.28));
            transition: transform 180ms cubic-bezier(.2, .8, .2, 1), filter 180ms ease;
        }

        html.dark .srd-game-card__badge {
            color: #111827 !important;
        }

        .srd-game-card__badge:hover {
            filter: drop-shadow(0 13px 20px rgba(37, 99, 235, 0.36));
            transform: translateY(-2px) rotate(-2deg) scale(1.04);
        }

        .srd-game-card__badge img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .srd-game-card__badge .srd-theme-logo--dark {
            display: none;
        }

        html.dark .srd-game-card__badge .srd-theme-logo--light {
            display: none;
        }

        html.dark .srd-game-card__badge .srd-theme-logo--dark {
            display: block;
        }

        .srd-game-card__header h1 {
            margin: 0;
            color: #0f172a;
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1.2;
        }

        html.dark .srd-game-card__header h1 {
            color: #f8fafc;
        }

        .srd-game-card__header p {
            margin: 0.35rem 0 0;
            color: #2563eb;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.16em;
        }

        html.dark .srd-game-card__header p {
            color: #60a5fa;
        }

        .srd-game-card__header > span {
            display: block;
            margin-top: 0.65rem;
            color: #64748b;
            font-size: 0.78rem;
        }

        html.dark .srd-game-card__header > span {
            color: #94a3b8;
        }

        .srd-game-card__form {
            margin-top: 1.6rem;
        }

        .srd-game-card__form .fi-sc-form,
        .srd-game-card__form .fi-sc-form > .fi-grid,
        .srd-game-card__form .fi-sc-form > .fi-grid > .fi-grid-col,
        .srd-game-card__form .fi-sc-form > .fi-grid > .fi-grid-col > .fi-sc-component,
        .srd-game-card__form .fi-sc-form > .fi-grid > .fi-grid-col > .fi-sc-component > .fi-grid,
        .srd-game-card__form .fi-fo-component-ctn {
            display: block !important;
            gap: 0 !important;
            row-gap: 0 !important;
        }

        .srd-game-card__form .srd-login-field,
        .srd-game-card__form .srd-login-options,
        .srd-game-card__form .srd-login-turnstile,
        .srd-game-card__form .fi-sc-form > .fi-grid:nth-child(2) {
            margin: 0 !important;
        }

        .srd-game-card__form .srd-login-field--password { margin-top: 1rem !important; }
        .srd-game-card__form .srd-login-options { margin-top: 0.65rem !important; }
        .srd-game-card__form .srd-login-turnstile,
        .srd-game-card__form .fi-sc-form > .fi-grid:nth-child(2) { margin-top: 1rem !important; }
        .srd-game-card__form .fi-fo-field { gap: 0 !important; }
        .srd-game-card__form .fi-fo-field-label-col { margin: 0 0 0.5rem !important; }

        .srd-game-card__form .fi-fo-field-label,
        .srd-game-card__form .fi-fo-field-label-content {
            color: #334155 !important;
            font-size: 0.7rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        html.dark .srd-game-card__form .fi-fo-field-label,
        html.dark .srd-game-card__form .fi-fo-field-label-content {
            color: #cbd5e1 !important;
        }

        .srd-game-card__form .fi-input-wrp {
            min-height: 3.15rem !important;
            overflow: hidden;
            border: 1px solid #cbd5e1 !important;
            border-radius: 0.9rem !important;
            background: rgba(248, 250, 252, 0.84) !important;
            box-shadow: 0 3px 9px rgba(15, 23, 42, 0.04) !important;
            transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease !important;
        }

        html.dark .srd-game-card__form .fi-input-wrp {
            border-color: rgba(148, 163, 184, 0.24) !important;
            background: rgba(2, 6, 23, 0.58) !important;
        }

        .srd-game-card__form .fi-input-wrp:hover {
            border-color: #60a5fa !important;
            transform: scale(1.02);
        }

        .srd-game-card__form .fi-input-wrp:focus-within {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18), 0 8px 18px rgba(37, 99, 235, 0.1) !important;
            transform: scale(1.02);
        }

        .srd-game-card__form .fi-input-wrp-prefix,
        .srd-game-card__form .fi-input-wrp-suffix {
            padding-inline: 0.85rem !important;
            border: 0 !important;
            background: transparent !important;
        }

        .srd-game-card__form .fi-input-wrp-prefix .fi-icon,
        .srd-game-card__form .fi-input-wrp-suffix .fi-icon {
            width: 1.15rem !important;
            height: 1.15rem !important;
            color: #64748b !important;
        }

        .srd-game-card__form .fi-input {
            min-height: 3rem !important;
            padding-inline: 0.3rem !important;
            color: #0f172a !important;
            font-size: 0.86rem !important;
        }

        html.dark .srd-game-card__form .fi-input { color: #f8fafc !important; }
        .srd-game-card__form .fi-input::placeholder { color: #64748b !important; opacity: 1; }

        .srd-game-card__form .srd-login-options > .fi-grid {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) auto !important;
            align-items: center !important;
            gap: 0.75rem !important;
        }

        .srd-game-card__form .srd-login-options .fi-grid-col {
            width: auto !important;
            margin: 0 !important;
            grid-column: auto !important;
        }

        .srd-game-card__form .srd-login-options .fi-grid-col:last-child .fi-sc-component,
        .srd-game-card__form .srd-login-reset-cell {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .srd-game-card__form .srd-login-remember .fi-fo-field-label {
            display: inline-flex !important;
            align-items: center;
            gap: 0.5rem;
            min-height: 2rem;
            letter-spacing: 0;
            cursor: pointer;
        }

        .srd-game-card__form .srd-login-remember .fi-checkbox-input {
            width: 1rem !important;
            height: 1rem !important;
            margin: 0 !important;
            border-color: #94a3b8 !important;
            border-radius: 0.32rem !important;
            accent-color: #2563eb;
        }

        .srd-game-card__form .srd-login-reset-link {
            color: #2563eb !important;
            font-size: 0.72rem;
            font-weight: 750;
            text-decoration: none;
            white-space: nowrap;
            transition: color 160ms ease, transform 160ms ease;
        }

        .srd-game-card__form .srd-login-reset-link:hover {
            color: #1d4ed8 !important;
            transform: translateY(-1px);
        }

        html.dark .srd-game-card__form .srd-login-reset-link { color: #60a5fa !important; }

        .srd-game-card__form .srd-login-turnstile,
        .srd-game-card__form .srd-login-turnstile .fi-fo-field-content-col,
        .srd-game-card__form .srd-login-turnstile .fi-fo-turnstile,
        .srd-game-card__form .srd-login-turnstile .w-full,
        .srd-game-card__form .srd-login-turnstile iframe {
            width: 100% !important;
            max-width: 100% !important;
        }

        .srd-game-card__form .srd-login-turnstile {
            min-height: 4.2rem !important;
            padding: 0.18rem !important;
            overflow: hidden !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 0.9rem !important;
            background: rgba(248, 250, 252, 0.7) !important;
        }

        html.dark .srd-game-card__form .srd-login-turnstile {
            border-color: rgba(148, 163, 184, 0.2) !important;
            background: rgba(2, 6, 23, 0.42) !important;
        }

        .srd-game-card__form .fi-form-actions {
            display: grid !important;
            gap: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .srd-game-card__form .srd-login-submit.fi-btn {
            width: 100% !important;
            min-height: 3.25rem !important;
            margin: 0 !important;
            border: 1px solid rgba(255, 255, 255, 0.18) !important;
            border-radius: 0.9rem !important;
            background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
            box-shadow: 0 12px 28px rgba(37, 99, 235, 0.32), inset 0 1px rgba(255, 255, 255, 0.18) !important;
            color: #fff !important;
            font-size: 0.88rem !important;
            font-weight: 800 !important;
            transition: box-shadow 160ms ease, filter 160ms ease, transform 120ms ease !important;
        }

        .srd-game-card__form .srd-login-submit.fi-btn:hover:not(:disabled) {
            filter: brightness(1.08);
            box-shadow: 0 17px 34px rgba(37, 99, 235, 0.4), inset 0 1px rgba(255, 255, 255, 0.22) !important;
            transform: translateY(-2px) scale(1.02);
        }

        .srd-game-card__form .srd-login-submit.fi-btn:active:not(:disabled) {
            transform: translateY(0) scale(0.98);
        }

        .srd-game-card__form .fi-fo-field-wrp-error-message {
            margin-top: 0.4rem !important;
            color: #ef4444 !important;
            font-size: 0.73rem !important;
        }

        .srd-game-card__status {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            margin-top: 1.2rem;
            color: #64748b;
            font-size: 0.68rem;
            font-weight: 650;
        }

        .srd-game-card__status span {
            width: 0.42rem;
            height: 0.42rem;
            border-radius: 999px;
            background: #22c55e;
            box-shadow: 0 0 10px rgba(34, 197, 94, 0.75);
            animation: srd-status-pulse 1.8s ease-in-out infinite;
        }

        html.dark .srd-game-card__status { color: #94a3b8; }

        @keyframes srd-orb-float {
            to { transform: translate3d(2rem, 1rem, 0) scale(1.08); }
        }

        @keyframes srd-card-enter {
            from {
                opacity: 0;
                transform: perspective(1000px) translateY(20px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: perspective(1000px) translateY(0) scale(1);
            }
        }

        @keyframes srd-status-pulse {
            50% { opacity: 0.5; transform: scale(0.78); }
        }

        @media (max-width: 420px) {
            .srd-game-login { align-items: flex-start; padding: 4.75rem 0.75rem 1.25rem; }
            .srd-game-login__theme { top: 0.85rem; right: 0.85rem; }
            .srd-game-card { padding: 1.45rem; border-radius: 1.2rem; }
            .srd-game-card__header h1 { font-size: 1.45rem; }
            .srd-game-card__form .srd-login-options > .fi-grid { gap: 0.5rem !important; }
        }

        @media (prefers-reduced-motion: reduce) {
            .srd-game-login *,
            .srd-game-login *::before,
            .srd-game-login *::after {
                scroll-behavior: auto !important;
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }

            .srd-game-card,
            .srd-game-card:hover {
                opacity: 1;
                transform: none;
            }
        }
    </style>
</x-filament-panels::page.simple>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light dark">
        <title>Sistem Rapor Digital</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}">
        @fonts
        <style>
            :root {
                --page: #f7faff;
                --surface: #ffffff;
                --surface-soft: #eef5ff;
                --surface-muted: #f1f5fb;
                --border: #dfe7f3;
                --ink: #172033;
                --muted: #4b5871;
                --muted-soft: #667085;
                --primary: #1769ff;
                --primary-strong: #0f55d9;
                --emerald: #13a76b;
                --shadow: 0 24px 70px rgba(23, 32, 51, 0.12);
                --shadow-soft: 0 12px 28px rgba(23, 32, 51, 0.08);
                --radius: 12px;
                --radius-lg: 18px;
            }

            * { box-sizing: border-box; }
            html { color-scheme: light; scroll-behavior: smooth; }
            body {
                background: var(--page);
                color: var(--ink);
                font-family: "Instrument Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                margin: 0;
                min-height: 100vh;
                overflow-x: hidden;
                position: relative;
            }

            body::before {
                content: "";
                position: fixed;
                inset: 0;
                pointer-events: none;
                z-index: -1;
                background:
                    radial-gradient(640px 380px at 12% 8%, rgba(23, 105, 255, 0.09), transparent 60%),
                    radial-gradient(520px 360px at 88% 16%, rgba(14, 165, 166, 0.09), transparent 60%),
                    radial-gradient(720px 480px at 50% 100%, rgba(23, 105, 255, 0.04), transparent 70%);
            }

            a { color: inherit; text-decoration: none; }
            .shell {
                margin: 0 auto;
                max-width: 1220px;
                min-height: 100vh;
                padding: 18px 28px 18px;
                display: flex;
                flex-direction: column;
            }

            .topbar {
                align-items: center;
                display: flex;
                gap: 22px;
                justify-content: space-between;
                padding: 2px 0;
            }

            .brand {
                align-items: center;
                display: inline-flex;
                gap: 14px;
                font-weight: 800;
            }

            .brand-logo { display: inline-flex; align-items: center; gap: 12px; }

            .brand-logo__mark {
                align-items: center;
                background: transparent;
                border-radius: 10px;
                display: inline-flex;
                flex: none;
                height: 58px;
                justify-content: center;
                overflow: hidden;
                width: 250px;
            }

            .brand-logo__mark img {
                display: block;
                height: 100%;
                width: 100%;
                object-fit: contain;
                object-position: left center;
                image-rendering: -webkit-optimize-contrast;
            }

            .brand-logo__mark .brand-logo__image--dark { display: none; }

            .topbar-actions { display: flex; align-items: center; gap: 10px; }

            .button {
                align-items: center;
                border: 1px solid transparent;
                border-radius: 10px;
                display: inline-flex;
                font-weight: 800;
                justify-content: center;
                letter-spacing: -0.01em;
                min-height: 42px;
                padding: 0 20px;
                transition: all 0.18s ease;
                font-size: 14px;
                line-height: 1;
                white-space: nowrap;
            }

            .button--primary {
                background: var(--primary);
                border-color: var(--primary);
                color: #fff;
                box-shadow: 0 8px 18px rgba(23, 105, 255, 0.22);
            }

            .button--primary:hover {
                background: var(--primary-strong);
                border-color: var(--primary-strong);
                transform: translateY(-1px);
                box-shadow: 0 12px 24px rgba(23, 105, 255, 0.28);
            }

            .button--secondary {
                background: var(--surface);
                border-color: var(--border);
                color: var(--ink);
                box-shadow: 0 4px 12px rgba(23, 32, 51, 0.05);
            }

            .button--secondary:hover {
                border-color: #c9d7ef;
                background: #fff;
                transform: translateY(-1px);
            }

            .hero-grid {
                display: grid;
                grid-template-columns: 0.98fr 1.02fr;
                gap: 36px;
                align-items: center;
                flex: 1;
                padding: 18px 0 12px;
            }

            .hero-left {
                display: grid;
                gap: 16px;
                max-width: 510px;
            }

            .eyebrow {
                align-items: center;
                background: rgba(23, 105, 255, 0.10);
                border: 1px solid rgba(23, 105, 255, 0.18);
                border-radius: 999px;
                color: var(--primary);
                display: inline-flex;
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.08em;
                padding: 5px 11px;
                text-transform: uppercase;
                width: fit-content;
            }

            h1 {
                font-size: clamp(2rem, 2.9vw, 2.55rem);
                line-height: 1.1;
                letter-spacing: -0.042em;
                margin: 0;
                font-weight: 800;
                max-width: 510px;
            }

            .lead {
                color: var(--muted);
                font-size: 15px;
                font-weight: 500;
                line-height: 1.6;
                margin: 0;
                max-width: 44ch;
            }

            .summary-strip {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 10px;
                margin-top: 10px;
                grid-auto-rows: 1fr;
                align-items: stretch;
            }

            .summary-item {
                background: rgba(255, 255, 255, 0.94);
                border: 1px solid rgba(223, 231, 243, 0.92);
                border-radius: var(--radius);
                box-shadow: var(--shadow-soft);
                padding: 12px 12px 11px;
                display: grid;
                gap: 8px;
                align-content: start;
                min-height: 112px;
                transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
            }

            .summary-item:hover {
                transform: translateY(-2px);
                border-color: #c9d7ef;
                box-shadow: 0 14px 24px rgba(23, 32, 51, 0.09);
            }

            .summary-icon {
                align-items: center;
                background: var(--surface-soft);
                border: 1px solid rgba(23, 105, 255, 0.16);
                border-radius: 9px;
                color: var(--primary);
                display: inline-flex;
                height: 32px;
                justify-content: center;
                width: 32px;
            }

            .summary-icon svg { width: 18px; height: 18px; display: block; }

            .summary-item strong {
                color: var(--ink);
                font-size: 13.8px;
                font-weight: 800;
                line-height: 1.2;
            }

            .summary-item span:last-child {
                color: var(--muted);
                font-size: 12.6px;
                font-weight: 500;
                line-height: 1.5;
                display: -webkit-box;
                -webkit-line-clamp: 3;
                -webkit-box-orient: vertical;
                overflow: hidden;
                min-height: 2.8em;
            }

            .hero-right {
                display: grid;
                place-items: start center;
                min-width: 0;
                margin-top: -28px;
            }

            .preview-card {
                background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
                border: 1px solid rgba(223, 231, 243, 0.96);
                border-radius: var(--radius-lg);
                box-shadow: 0 26px 68px rgba(23, 32, 51, 0.14), 0 2px 0 rgba(255,255,255,0.8) inset;
                max-width: 630px;
                overflow: hidden;
                position: relative;
                width: 100%;
            }

            .preview-card::before {
                content: "";
                position: absolute;
                inset: auto -20% -18% -20%;
                height: 42%;
                background: radial-gradient(60% 80% at 30% 0%, rgba(23,105,255,0.16), transparent 70%),
                            radial-gradient(60% 80% at 80% 0%, rgba(14,165,166,0.14), transparent 70%);
                pointer-events: none;
                z-index: 0;
            }

            .preview-topbar {
                align-items: center;
                background: linear-gradient(180deg, #f9fbff, #eef4ff);
                border-bottom: 1px solid var(--border);
                display: flex;
                gap: 8px;
                height: 36px;
                padding: 0 14px;
                position: relative;
                z-index: 1;
            }

            .preview-dot { border-radius: 50%; height: 8px; width: 8px; display: block; }
            .preview-dot:nth-child(1) { background: #ff5f57; }
            .preview-dot:nth-child(2) { background: #febc2e; }
            .preview-dot:nth-child(3) { background: #28c840; }

            .preview-topbar span:last-child {
                color: #8ea0b8;
                font-size: 10.5px;
                font-weight: 700;
                letter-spacing: 0.04em;
                margin-left: 10px;
                text-transform: uppercase;
            }

            .preview-card img {
                display: block;
                height: auto;
                width: 100%;
                position: relative;
                z-index: 1;
                image-rendering: -webkit-optimize-contrast;
            }

            .preview-caption {
                background: rgba(255,255,255,0.90);
                backdrop-filter: blur(8px);
                border-top: 1px solid var(--border);
                display: flex;
                gap: 9px;
                align-items: center;
                padding: 9px 13px;
                position: relative;
                z-index: 1;
            }

            .preview-caption__dot {
                background: var(--emerald);
                border-radius: 50%;
                box-shadow: 0 0 0 4px rgba(19,167,107,0.15);
                height: 7px;
                width: 7px;
                flex: none;
            }

            .preview-caption__text {
                color: var(--muted);
                font-size: 12px;
                font-weight: 600;
                line-height: 1.35;
            }

            @media (prefers-color-scheme: dark) {
                :root {
                    --page: #101827;
                    --surface: #182132;
                    --surface-soft: #1e2a40;
                    --border: #2f3b52;
                    --ink: #f5f7fb;
                    --muted: #c0cad8;
                    --muted-soft: #9aa6b9;
                    --shadow: 0 24px 70px rgba(0,0,0,0.40);
                    --shadow-soft: 0 14px 32px rgba(0,0,0,0.28);
                }
                .brand-logo__mark .brand-logo__image--light { display: none; }
                .brand-logo__mark .brand-logo__image--dark { display: block; }
                html { color-scheme: dark; }
                body { background: var(--page); }
                body::before {
                    background:
                        radial-gradient(640px 380px at 12% 8%, rgba(23,105,255,0.16), transparent 60%),
                        radial-gradient(520px 360px at 88% 16%, rgba(14,165,166,0.16), transparent 60%),
                        radial-gradient(720px 480px at 50% 100%, rgba(0,0,0,0.28), transparent 70%);
                }
                .eyebrow { background: rgba(23,105,255,0.18); border-color: rgba(23,105,255,0.30); color: #8eb6ff; }
                .button--secondary { background: rgba(24,33,50,0.96); border-color: rgba(255,255,255,0.14); color: var(--ink); }
                .button--secondary:hover { background: #1f2c45; border-color: rgba(255,255,255,0.22); }
                .summary-item { background: rgba(24,33,50,0.92); border-color: rgba(255,255,255,0.10); }
                .summary-icon { background: rgba(23,105,255,0.14); border-color: rgba(23,105,255,0.24); }
                .preview-card { background: linear-gradient(180deg, #1a2538 0%, #162032 100%); border-color: rgba(255,255,255,0.10); }
                .preview-topbar { background: linear-gradient(180deg, #1e2d49, #162032); border-bottom-color: rgba(255,255,255,0.08); }
                .preview-caption { background: rgba(22,32,50,0.86); border-top-color: rgba(255,255,255,0.08); }
            }

            @media (max-width: 1366px) and (max-height: 800px) {
                .shell { padding: 14px 26px 12px; }
                .hero-grid { padding: 10px 0 8px; gap: 30px; }
                .hero-right { margin-top: -20px; }
                .preview-card { max-width: 590px; }
                .summary-item { min-height: 102px; padding: 11px 11px 10px; }
            }

            @media (max-width: 980px) {
                .shell { padding: 16px 16px 18px; }
                .hero-grid { grid-template-columns: 1fr; gap: 22px; padding: 20px 0 10px; }
                .hero-left { max-width: 100%; gap: 14px; }
                h1 { max-width: 100%; font-size: clamp(1.95rem, 5.8vw, 2.45rem); }
                .lead { max-width: 100%; }
                .hero-right { margin-top: 0; place-items: center; }
                .preview-card { transform: none; max-width: 100%; }
                .summary-strip { grid-template-columns: repeat(2, minmax(0,1fr)); }
            }

            @media (max-width: 720px) {
                .shell { padding: 12px 12px 16px; }
                .brand-logo__mark { height: 46px; width: 259px; }
                .topbar-actions .button { min-height: 38px; padding: 0 14px; font-size: 13px; }
                .summary-strip { grid-template-columns: 1fr; gap: 8px; }
                .summary-item { min-height: auto; }
                h1 { font-size: clamp(1.8rem, 7.2vw, 2.15rem); line-height: 1.08; }
                .lead { font-size: 14.2px; }
                .preview-topbar { height: 32px; }
            }

            @media (max-width: 380px) {
                .brand-logo__mark { height: 40px; width: 225px; }
                h1 { font-size: 1.65rem; }
            }

            .button:focus-visible, .brand:focus-visible { outline: 3px solid rgba(23,105,255,0.35); outline-offset: 3px; }
        </style>
    </head>
    <body>
        @php
            $lightLogoPath = 'logo/logo-tema-light.svg';
            $darkLogoPath = 'logo/logo-tema-dark.svg';
            $lightLogoVersion = file_exists(public_path($lightLogoPath)) ? filemtime(public_path($lightLogoPath)) : time();
            $darkLogoVersion = file_exists(public_path($darkLogoPath)) ? filemtime(public_path($darkLogoPath)) : time();
        @endphp

        <main>
            <div class="shell">
                <header class="topbar">
                    <a class="brand" href="{{ url('/') }}" aria-label="Sistem Rapor Digital - Beranda">
                        <span class="brand-logo">
                            <span class="brand-logo__mark">
                                <img class="brand-logo__image--light" src="{{ asset($lightLogoPath) }}?v={{ $lightLogoVersion }}" alt="Sistem Rapor Digital">
                                <img class="brand-logo__image--dark" src="{{ asset($darkLogoPath) }}?v={{ $darkLogoVersion }}" alt="" aria-hidden="true">
                            </span>
                        </span>
                    </a>
                    <div class="topbar-actions">
                        <a class="button button--primary" href="{{ url('/admin') }}">Masuk ke Sistem</a>
                    </div>
                </header>

                <section class="hero-grid" aria-label="Sistem Rapor Digital">
                    <div class="hero-left">
                        <div>
                            <span class="eyebrow">Portal Rapor Sekolah</span>
                            <h1 style="margin-top:12px">Kelola nilai, pantau progres, dan terbitkan rapor dalam satu sistem terintegrasi.</h1>
                        </div>

                        <p class="lead">Dirancang untuk mempermudah guru dalam pengisian rapor. Input nilai lebih rapi, progres jelas, dan cetak PDF tanpa proses berulang.</p>

                        <div class="summary-strip" id="fitur" aria-label="Fitur utama">
                            <div class="summary-item">
                                <span class="summary-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><path d="M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2"/><path d="M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2"/><path d="M9 12h6"/><path d="M9 16h6"/></svg>
                                </span>
                                <strong>Input Nilai</strong>
                                <span>Guru mengelola nilai siswa per mata pelajaran dan kelas.</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 16l4-4 4 4 6-6"/></svg>
                                </span>
                                <strong>Pantau Progres</strong>
                                <span>Admin memantau kelengkapan dan finalisasi rapor.</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                                </span>
                                <strong>Cetak Rapor</strong>
                                <span>Rapor dapat ditinjau dan dicetak dalam format PDF.</span>
                            </div>
                        </div>
                    </div>

                    <div class="hero-right" aria-hidden="true">
                        <div class="preview-card">
                            <div class="preview-topbar">
                                <span class="preview-dot"></span><span class="preview-dot"></span><span class="preview-dot"></span>
                                <span>Preview Dashboard</span>
                            </div>
                            <img src="{{ asset('ui-concepts/raport-dashboard-concept.png') }}" alt="Pratinjau dashboard Sistem Rapor Digital" loading="eager" decoding="async">
                            <div class="preview-caption">
                                <span class="preview-caption__dot"></span>
                                <span class="preview-caption__text">Finalisasi lebih cepat • Data terpusat • Akses berbasis peran</span>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>

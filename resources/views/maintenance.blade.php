<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $setting->title }} - Sistem Rapor Digital</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 1.25rem; background: radial-gradient(circle at top left, #dbeafe 0, transparent 38%), #f8fafc; color: #0f172a; }
        main { width: min(100%, 42rem); overflow: hidden; border: 1px solid #dbe4f0; border-radius: 1.5rem; background: rgba(255,255,255,.94); box-shadow: 0 30px 80px rgba(15,23,42,.14); }
        .accent { height: .4rem; background: linear-gradient(90deg, #1769ff, #06b6d4); }
        .content { padding: clamp(1.5rem, 6vw, 3rem); }
        .mark { display: grid; width: 3.25rem; height: 3.25rem; place-items: center; margin-bottom: 1.5rem; border-radius: 1rem; background: #eff6ff; color: #1769ff; }
        .mark svg { width: 1.75rem; height: 1.75rem; }
        .eyebrow { margin: 0 0 .55rem; color: #1769ff; font-size: .75rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
        h1 { margin: 0; font-size: clamp(2rem, 7vw, 3.5rem); letter-spacing: -.045em; line-height: 1.05; }
        .message { margin: 1.25rem 0 0; color: #475569; font-size: 1.05rem; line-height: 1.75; white-space: pre-line; }
        .estimate { display: flex; align-items: center; gap: .9rem; margin-top: 1.75rem; padding: 1rem; border: 1px solid #bfdbfe; border-radius: 1rem; background: #eff6ff; }
        .estimate strong { display: block; color: #1e3a8a; }
        .estimate span { display: block; margin-top: .15rem; color: #475569; font-size: .9rem; }
        .admin { display: inline-flex; min-height: 2.75rem; align-items: center; justify-content: center; margin-top: 1.75rem; border: 1px solid #cbd5e1; border-radius: .8rem; padding: .7rem 1rem; color: #334155; font-weight: 750; text-decoration: none; }
        .admin:hover, .admin:focus-visible { border-color: #1769ff; color: #1769ff; outline: 2px solid #93c5fd; outline-offset: 2px; }
        @media (prefers-color-scheme: dark) { :root { color-scheme: dark; } body { background: radial-gradient(circle at top left, #172554 0, transparent 38%), #020617; color: #f8fafc; } main { border-color: #1e293b; background: rgba(15,23,42,.95); } .mark, .estimate { border-color: #1e3a8a; background: rgba(30,58,138,.24); } .message, .estimate span { color: #cbd5e1; } .estimate strong { color: #bfdbfe; } .admin { border-color: #475569; color: #e2e8f0; } }
    </style>
</head>
<body>
    <main>
        <div class="accent"></div>
        <div class="content">
            <div class="mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v3m0 12v3M3 12h3m12 0h3M5.64 5.64l2.12 2.12m8.48 8.48 2.12 2.12m0-12.72-2.12 2.12m-8.48 8.48-2.12 2.12"/><circle cx="12" cy="12" r="4"/></svg>
            </div>
            <p class="eyebrow">Website Maintenance</p>
            <h1>{{ $setting->title }}</h1>
            <p class="message">{{ $setting->message }}</p>
            @if ($setting->ends_at)
                <div class="estimate">
                    <div>
                        <strong>Estimasi selesai</strong>
                        <span>{{ $setting->ends_at->locale('id')->translatedFormat('l, d F Y') }}</span>
                    </div>
                </div>
            @endif
            <a class="admin" href="{{ route('maintenance.admin.login') }}">Masuk Sebagai Administrator</a>
        </div>
    </main>
</body>
</html>

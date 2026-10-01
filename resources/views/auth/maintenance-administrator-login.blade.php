<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Masuk Sebagai Administrator</title>
    <style>
        :root { font-family: Inter, ui-sans-serif, system-ui, sans-serif; color-scheme: light dark; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 1rem; background: #f1f5f9; color: #0f172a; }
        main { width: min(100%, 27rem); border: 1px solid #dbe4f0; border-radius: 1.25rem; padding: 2rem; background: #fff; box-shadow: 0 25px 65px rgba(15,23,42,.14); }
        h1 { margin: 0; font-size: 1.6rem; letter-spacing: -.03em; }
        p { margin: .6rem 0 1.5rem; color: #64748b; line-height: 1.6; }
        label { display: block; margin-top: 1rem; font-size: .82rem; font-weight: 750; }
        input[type=text], input[type=password] { width: 100%; height: 3rem; margin-top: .4rem; border: 1px solid #cbd5e1; border-radius: .75rem; padding: 0 .85rem; background: transparent; color: inherit; font: inherit; }
        input:focus { border-color: #1769ff; outline: 2px solid #bfdbfe; outline-offset: 1px; }
        .remember { display: flex; align-items: center; gap: .5rem; }
        .error { margin-top: .45rem; color: #dc2626; font-size: .8rem; }
        button { width: 100%; min-height: 3rem; margin-top: 1.4rem; border: 0; border-radius: .75rem; background: #1769ff; color: #fff; font: inherit; font-weight: 800; cursor: pointer; }
        button:focus-visible { outline: 2px solid #60a5fa; outline-offset: 3px; }
        a { display: block; margin-top: 1rem; color: #475569; text-align: center; }
        @media (prefers-color-scheme: dark) { body { background: #020617; color: #f8fafc; } main { border-color: #334155; background: #0f172a; } p, a { color: #cbd5e1; } input[type=text], input[type=password] { border-color: #475569; } }
    </style>
</head>
<body>
    <main>
        <h1>Masuk Sebagai Administrator</h1>
        <p>Jalur ini hanya menerima akun dengan role Administrator selama maintenance berlangsung.</p>
        <form method="POST" action="{{ route('maintenance.admin.authenticate') }}">
            @csrf
            <label for="login">Username atau email</label>
            <input id="login" name="login" type="text" value="{{ old('login') }}" required autofocus autocomplete="username">
            @error('login') <div class="error" role="alert">{{ $message }}</div> @enderror
            <label for="password">Kata sandi</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
            <label class="remember"><input name="remember" type="checkbox" value="1"> Ingat saya</label>
            <button type="submit">Masuk sebagai Administrator</button>
        </form>
        <a href="{{ url('/') }}">Kembali ke informasi maintenance</a>
    </main>
</body>
</html>

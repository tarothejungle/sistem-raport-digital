<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Sistem Rapor Digital membantu sekolah mengelola data siswa, absensi, nilai, dan cetak rapor dalam satu tempat.">
        <meta name="theme-color" content="#e9eef4">
        <title>Sistem Rapor Digital — Dari kelas sampai rapor</title>
        <link rel="icon" type="image/png" href="/logo/logo-baru-dark.png?v={{ filemtime(public_path('logo/logo-baru-dark.png')) }}">
        @fonts
        @vite('resources/css/app.css')
        <style>
            :root {
                --surface: #e9eef4;
                --ink: #172e42;
                --muted: #4e6474;
                --blue: #205b91;
                --raised: 9px 9px 22px #cbd3dd, -9px -9px 22px #ffffff;
                --inset: inset 5px 5px 12px #cbd3dd, inset -5px -5px 12px #ffffff;
            }
            body { margin: 0; background: var(--surface); color: var(--ink); font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
            *, *::before, *::after { box-sizing: border-box; }
            a { color: inherit; text-decoration: none; }
            a:focus-visible { outline: 3px solid var(--blue); outline-offset: 5px; }
            .wrap { width: min(1140px, calc(100% - 48px)); margin-inline: auto; }
            .site-header { padding: 24px 0; }
            .nav { display: flex; align-items: center; justify-content: space-between; gap: 24px; }
            .brand { display: inline-flex; align-items: center; gap: 12px; min-width: 0; font-weight: 800; font-size: 17px; letter-spacing: -.035em; }
            .brand img { width: 48px; height: 48px; object-fit: contain; }
            .brand span { max-width: 180px; line-height: 1.15; }
            .nav-links { display: flex; align-items: center; gap: 32px; color: var(--muted); font-size: 14px; font-weight: 650; }
            .nav-links a:hover, .footer a:hover { color: var(--blue); }
            .button { display: inline-flex; min-height: 48px; align-items: center; justify-content: center; gap: 10px; padding: 12px 23px; border-radius: 16px; font-weight: 750; font-size: 14px; transition: box-shadow .18s ease, background-color .18s ease, color .18s ease; text-align: center; }
            .button-primary { background: #205b91; color: #fff; box-shadow: 5px 5px 12px #bec9d4, -5px -5px 12px #fff; }
            .button-primary:hover { background: #174c7d; }
            .button-primary:active { box-shadow: inset 4px 4px 9px #123c66, inset -4px -4px 9px #3575aa; }
            .button-soft { background: var(--surface); color: var(--blue); box-shadow: var(--raised); }
            .button-soft:hover { color: #123e68; box-shadow: 5px 5px 13px #cbd3dd, -5px -5px 13px #fff; }
            .button-soft:active { box-shadow: var(--inset); }
            .hero { display: grid; grid-template-columns: 1.02fr .98fr; align-items: center; gap: clamp(42px, 7vw, 100px); padding-block: 90px 120px; }
            .eyebrow { display: inline-flex; align-items: center; gap: 10px; color: var(--blue); font-size: 12px; font-weight: 800; letter-spacing: .15em; text-transform: uppercase; }
            .eyebrow::before { content: ''; width: 22px; height: 2px; background: currentColor; }
            h1, h2, h3, p { margin-top: 0; }
            h1 { max-width: 630px; margin: 25px 0 22px; font-size: clamp(46px, 5.4vw, 76px); line-height: 1.05; letter-spacing: -.065em; font-weight: 800; }
            h1 span { color: var(--blue); }
            .hero-copy > p { max-width: 530px; margin-bottom: 30px; color: var(--muted); font-size: 18px; line-height: 1.75; }
            .hero-actions { display: flex; flex-wrap: wrap; gap: 15px; }
            .hero-note { margin: 25px 0 0 !important; font-size: 13px !important; line-height: 1.5 !important; }
            .preview { padding: 26px; border-radius: 32px; background: var(--surface); box-shadow: 15px 15px 34px #c8d1db, -15px -15px 34px #fff; }
            .preview-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 24px; font-size: 12px; font-weight: 750; color: var(--muted); }
            .preview-top strong { color: var(--blue); font-size: 12px; }
            .preview-heading { margin-bottom: 18px; }
            .preview-heading strong { display: block; font-size: 22px; letter-spacing: -.045em; }
            .preview-heading span { display: block; margin-top: 5px; color: var(--muted); font-size: 13px; }
            .preview-stack { display: grid; gap: 14px; }
            .preview-row { display: flex; align-items: center; gap: 14px; padding: 17px; border-radius: 17px; background: var(--surface); box-shadow: var(--inset); }
            .preview-icon { display: grid; width: 42px; height: 42px; flex: none; place-items: center; border-radius: 13px; color: var(--blue); background: var(--surface); box-shadow: 4px 4px 9px #cbd3dd, -4px -4px 9px #fff; }
            .preview-icon svg, .feature-icon svg { width: 21px; height: 21px; }
            .preview-row strong { display: block; font-size: 14px; }
            .preview-row small { display: block; margin-top: 3px; color: var(--muted); font-size: 12px; line-height: 1.4; }
            .preview-step { margin-left: auto; color: var(--blue); font-size: 11px; font-weight: 800; white-space: nowrap; }
            .preview-foot { display: flex; align-items: center; gap: 8px; margin-top: 24px; color: var(--muted); font-size: 12px; }
            .preview-foot::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: #3c8a74; }
            .section { padding-block: 78px; scroll-margin-top: 30px; }
            .section-heading { max-width: 660px; }
            .section-heading h2, .cta h2 { margin: 17px 0; font-size: clamp(32px, 3.3vw, 48px); line-height: 1.15; letter-spacing: -.055em; }
            .section-heading p, .cta p { color: var(--muted); line-height: 1.7; font-size: 16px; }
            .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; margin-top: 38px; }
            .step, .feature { background: var(--surface); box-shadow: var(--raised); border-radius: 23px; }
            .step { min-height: 215px; padding: 28px; }
            .step-number { display: grid; width: 45px; height: 45px; place-items: center; border-radius: 13px; box-shadow: var(--inset); color: var(--blue); font-weight: 800; }
            .step h3, .feature h3 { margin: 23px 0 9px; font-size: 18px; letter-spacing: -.035em; }
            .step p, .feature p { margin: 0; color: var(--muted); font-size: 14px; line-height: 1.7; }
            .features { display: grid; grid-template-columns: repeat(2, 1fr); gap: 22px; margin-top: 38px; }
            .feature { display: flex; align-items: flex-start; gap: 21px; padding: 27px; }
            .feature-icon { display: grid; width: 48px; height: 48px; flex: none; place-items: center; border-radius: 15px; box-shadow: var(--inset); color: var(--blue); }
            .feature h3 { margin: 2px 0 9px; }
            .cta { display: flex; align-items: center; justify-content: space-between; gap: 32px; margin-block: 80px 95px; padding: 45px 50px; border-radius: 30px; box-shadow: var(--raised); }
            .cta h2 { max-width: 580px; }
            .cta p { max-width: 550px; margin-bottom: 0; }
            .cta .button { flex: none; }
            .footer { display: flex; justify-content: space-between; gap: 20px; padding-block: 30px 42px; border-top: 1px solid #d3dce5; color: var(--muted); font-size: 13px; }
            @media (max-width: 900px) {
                .hero { gap: 35px; padding-block: 65px 85px; }
                .preview { padding: 20px; }
                .nav-links { gap: 18px; }
                .step { padding: 22px; }
            }
            @media (max-width: 700px) {
                .wrap { width: min(100% - 36px, 520px); }
                .site-header { padding: 18px 0; }
                .brand { font-size: 14px; gap: 7px; }
                .brand img { width: 40px; height: 40px; }
                .brand span { max-width: 105px; }
                .nav-links { display: none; }
                .nav > .button { padding-inline: 17px; }
                .hero { grid-template-columns: 1fr; padding-block: 62px 65px; }
                h1 { font-size: clamp(43px, 10vw, 62px); }
                .hero-copy > p { font-size: 16px; }
                .section { padding-block: 60px; }
                .steps, .features { grid-template-columns: 1fr; }
                .step { min-height: 0; }
                .cta { display: block; margin-block: 60px; padding: 32px 25px; }
                .cta .button { margin-top: 25px; }
                .footer { flex-direction: column; }
            }
            @media (max-width: 380px) {
                .hero-actions .button { width: 100%; }
                .preview-step { display: none; }
            }
            /* Entrance + scroll-reveal animations */
            @keyframes srd-rise {
                from { opacity: 0; transform: translateY(26px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes srd-fade {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            [data-animate] { opacity: 0; }
            [data-animate].is-in {
                opacity: 1;
                animation: srd-rise .7s cubic-bezier(.22,1,.36,1) both;
                animation-delay: var(--delay, 0ms);
            }
            .site-header[data-animate].is-in { animation-name: srd-fade; }
            .preview[data-animate].is-in { animation-duration: .85s; }
            .preview-row { opacity: 0; }
            .preview.is-in .preview-row {
                animation: srd-rise .55s cubic-bezier(.22,1,.36,1) both;
            }
            .preview.is-in .preview-row:nth-child(1) { animation-delay: .25s; }
            .preview.is-in .preview-row:nth-child(2) { animation-delay: .37s; }
            .preview.is-in .preview-row:nth-child(3) { animation-delay: .49s; }

            @media (prefers-reduced-motion: reduce) {
                html { scroll-behavior: auto; }
                .button { transition: none; }
                [data-animate],
                [data-animate].is-in,
                .preview-row,
                .preview.is-in .preview-row {
                    opacity: 1 !important;
                    animation: none !important;
                    transform: none !important;
                }
            }
        </style>
    </head>
    <body>
        <header class="site-header" data-animate>
            <nav class="wrap nav" aria-label="Navigasi utama">
                <a class="brand" href="{{ url('/') }}" aria-label="Sistem Rapor Digital, beranda">
                    <img src="/logo/logo-baru-dark.png" alt="">
                    <span>Sistem Rapor Digital</span>
                </a>
                <div class="nav-links">
                    <a href="#cara-kerja">Cara kerja</a>
                    <a href="#fitur">Fitur</a>
                </div>
                <a class="button button-soft" href="{{ auth()->check() ? url('/admin') : route('login') }}">{{ auth()->check() ? 'Buka dashboard' : 'Masuk' }}</a>
            </nav>
        </header>

        <main>
            <section class="wrap hero" aria-labelledby="hero-title">
                <div class="hero-copy">
                    <span class="eyebrow" data-animate style="--delay:60ms">Rapor sekolah, lebih tertata</span>
                    <h1 id="hero-title" data-animate style="--delay:140ms">Dari kelas sampai rapor, <span>semuanya nyambung.</span></h1>
                    <p data-animate style="--delay:220ms">Data siswa, absensi, nilai, dan rapor tak perlu berpindah-pindah tempat. Admin menyiapkan datanya, guru mengisi sesuai kelasnya, lalu hasilnya siap dibagikan ke siswa.</p>
                    <div class="hero-actions" data-animate style="--delay:300ms">
                        <a class="button button-primary" href="{{ auth()->check() ? url('/admin') : route('login') }}">
                            {{ auth()->check() ? 'Buka dashboard' : 'Masuk ke sistem' }}
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" /></svg>
                        </a>
                        <a class="button button-soft" href="#cara-kerja">Lihat cara kerja</a>
                    </div>
                    <p class="hero-note" data-animate style="--delay:380ms">Untuk admin, guru, dan siswa dengan akses sesuai perannya.</p>
                </div>
                <div class="preview" data-animate style="--delay:300ms" aria-label="Gambaran alur kerja sistem">
                    <div class="preview-top"><span>ALUR AKADEMIK</span><strong>01 — 03</strong></div>
                    <div class="preview-heading"><strong>Satu alur, dari awal sampai selesai.</strong><span>Yang dikerjakan di kelas ikut terbawa ke rapor.</span></div>
                    <div class="preview-stack">
                        <div class="preview-row">
                            <span class="preview-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="17" rx="2" /><path d="M8 2v4m8-4v4M3 10h18m-13 5 2 2 5-5" /></svg></span>
                            <span><strong>Siapkan kelas & siswa</strong><small>Data dan penugasan guru tersusun.</small></span><span class="preview-step">01</span>
                        </div>
                        <div class="preview-row">
                            <span class="preview-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 4h11a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" /><path d="M10 9h7m-7 4h7m-7 4h4M3 8v10" /></svg></span>
                            <span><strong>Catat absen & nilai</strong><small>Guru mengisi sesuai kelas dan mapel.</small></span><span class="preview-step">02</span>
                        </div>
                        <div class="preview-row">
                            <span class="preview-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" /><path d="M14 2v6h6M8 13h8m-8 4h5" /></svg></span>
                            <span><strong>Cetak & bagikan rapor</strong><small>PDF siap unduh setelah nilai lengkap.</small></span><span class="preview-step">03</span>
                        </div>
                    </div>
                    <div class="preview-foot">Alur sederhana untuk pekerjaan sekolah sehari-hari</div>
                </div>
            </section>

            <section id="cara-kerja" class="wrap section" aria-labelledby="workflow-title">
                <div class="section-heading" data-animate>
                    <span class="eyebrow">Cara kerja</span>
                    <h2 id="workflow-title">Tak perlu mulai dari nol setiap kali mau buat rapor.</h2>
                    <p>Semua mengikuti alur kerja sekolah: siapkan data, isi kegiatan belajar, lalu terbitkan hasilnya.</p>
                </div>
                <div class="steps">
                    <article class="step" data-animate style="--delay:0ms"><span class="step-number">01</span><h3>Admin siapkan data</h3><p>Atur tahun ajaran, kelas, mapel, guru, dan siswa. Data siswa juga bisa diimpor lewat Excel atau CSV.</p></article>
                    <article class="step" data-animate style="--delay:110ms"><span class="step-number">02</span><h3>Guru isi yang diperlukan</h3><p>Catat absensi per kelas dan isi nilai per mata pelajaran, termasuk lewat template Excel.</p></article>
                    <article class="step" data-animate style="--delay:220ms"><span class="step-number">03</span><h3>Rapor siap diterbitkan</h3><p>Pantau kelengkapan nilai, cek hasil rapor, lalu unduh PDF satuan atau ZIP untuk beberapa siswa.</p></article>
                </div>
            </section>

            <section id="fitur" class="wrap section" aria-labelledby="features-title">
                <div class="section-heading" data-animate>
                    <span class="eyebrow">Yang bisa dikerjakan</span>
                    <h2 id="features-title">Hal penting di sekolah, dalam satu tempat.</h2>
                    <p>Bukan sekadar tempat mengisi nilai. Pekerjaan sebelum dan sesudahnya juga ikut tertangani.</p>
                </div>
                <div class="features">
                    <article class="feature" data-animate style="--delay:0ms"><span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" /><path d="m9 16 2 2 4-4" /></svg></span><div><h3>Absensi ikut masuk rapor</h3><p>Sakit, izin, dan alpa dicatat per kelas dan tahun ajaran, lalu muncul di rapor siswa.</p></div></article>
                    <article class="feature" data-animate style="--delay:90ms"><span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18M7 16l4-4 4 3 5-7" /></svg></span><div><h3>Nilai lebih mudah dikelola</h3><p>Guru mengisi nilai sesuai penugasan. Template dan impor Excel membantu saat mengerjakan banyak siswa sekaligus.</p></div></article>
                    <article class="feature" data-animate style="--delay:180ms"><span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h16M6 16V4h12v12M9 8h6m-6 4h6M3 16h18" /></svg></span><div><h3>Riwayat siswa tetap rapi</h3><p>Kenaikan kelas dan kelulusan punya alurnya sendiri, termasuk pencatatan alumni dan riwayat kelas.</p></div></article>
                    <article class="feature" data-animate style="--delay:270ms"><span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3c-4 0-7 3-7 7v4l-2 3h18l-2-3v-4c0-4-3-7-7-7Zm-2 18h4" /></svg></span><div><h3>Siswa melihat hasilnya</h3><p>Setelah akses nilai diaktifkan, siswa bisa masuk dan melihat nilai final miliknya sendiri.</p></div></article>
                </div>
            </section>

            <section class="wrap cta" data-animate aria-labelledby="cta-title">
                <div><span class="eyebrow">Mulai dari sini</span><h2 id="cta-title">Sudah punya akun? Lanjutkan pekerjaan di panel sekolah.</h2><p>Masuk untuk mengelola data, mengisi nilai, atau melihat hasil sesuai akses akunmu.</p></div>
                <a class="button button-primary" href="{{ auth()->check() ? url('/admin') : route('login') }}">{{ auth()->check() ? 'Buka dashboard' : 'Masuk ke sistem' }}</a>
            </section>
        </main>
        <footer class="wrap footer"><span>&copy; {{ now()->year }} Sistem Rapor Digital</span><a href="{{ url('/') }}">Kembali ke atas</a></footer>

        <script>
            (() => {
                const nodes = Array.from(document.querySelectorAll('[data-animate]'));
                const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                if (reduce || !('IntersectionObserver' in window)) {
                    nodes.forEach((node) => node.classList.add('is-in'));
                    return;
                }

                // Hero + header already in view on load: reveal immediately.
                const observer = new IntersectionObserver((entries, obs) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-in');
                            obs.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.16, rootMargin: '0px 0px -8% 0px' });

                nodes.forEach((node) => {
                    const rect = node.getBoundingClientRect();
                    if (rect.top < window.innerHeight * 0.92) {
                        node.classList.add('is-in');
                    } else {
                        observer.observe(node);
                    }
                });
            })();
        </script>
    </body>
</html>

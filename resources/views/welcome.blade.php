<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth bg-slate-950">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Sistem Rapor Digital menghubungkan pengelolaan data sekolah, input nilai, kenaikan kelas, portal siswa, notifikasi, dan penerbitan rapor PDF dalam satu panel.">
        <meta name="theme-color" content="#0b0f17">

        <title>Sistem Rapor Digital</title>

        <link rel="icon" type="image/png" href="{{ asset('logo/logo-rapor.png') }}">
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen overflow-x-hidden bg-slate-950 font-sans text-slate-100 antialiased selection:bg-blue-500/30 selection:text-white">
        <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden" aria-hidden="true">
            <svg class="absolute inset-0 h-full w-full opacity-[0.055]" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="tech-grid" width="42" height="42" patternUnits="userSpaceOnUse">
                        <path d="M 42 0 L 0 0 0 42" fill="none" stroke="white" stroke-width="1" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#tech-grid)" />
            </svg>
            <div data-ambient="left" class="absolute -top-24 left-[8%] h-96 w-96 rounded-full bg-blue-600/20 blur-[120px]"></div>
            <div data-ambient="right" class="absolute top-[32rem] right-[4%] h-80 w-80 rounded-full bg-cyan-500/10 blur-[120px]"></div>
        </div>

        <header data-enter="navbar" class="fixed top-4 left-1/2 z-50 flex w-[92%] max-w-5xl -translate-x-1/2 items-center justify-between rounded-full border border-white/10 bg-slate-900/70 px-4 py-3 shadow-2xl shadow-black/50 backdrop-blur-md sm:px-6">
            <nav class="contents" aria-label="Navigasi utama">
                <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-3 rounded-full focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-500" aria-label="Sistem Rapor Digital - Beranda">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center">
                        <img src="{{ asset('logo/logo-rapor-light.png') }}" alt="" class="h-full w-full object-contain">
                    </span>
                    <span class="min-w-0 leading-none">
                        <span class="block truncate text-lg font-bold tracking-tight text-white">Sistem Rapor<span class="text-cyan-400"> Digital</span></span>
                        <span class="mt-1 hidden text-[10px] tracking-wider text-slate-400 uppercase sm:block">Portal Akademik Sekolah</span>
                    </span>
                </a>

                <div class="hidden items-center gap-7 text-sm font-medium text-slate-300 md:flex">
                    <a href="#fitur" class="transition hover:text-white focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-500">Fitur</a>
                    <a href="#keunggulan" class="transition hover:text-white focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-500">Keunggulan</a>
                    <a href="#faq" class="transition hover:text-white focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-500">FAQ</a>
                </div>

                @guest
                    <a href="{{ route('login') }}" class="shrink-0 rounded-full bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-lg shadow-blue-500/20 transition hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-400 sm:px-5 sm:text-sm">
                        Masuk
                    </a>
                @else
                    <a href="{{ url('/admin') }}" class="shrink-0 rounded-full bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-lg shadow-blue-500/20 transition hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-400 sm:px-5 sm:text-sm">
                        Dashboard
                    </a>
                @endguest
            </nav>
        </header>

        <main>
            <section class="relative mx-auto grid min-h-screen max-w-7xl grid-cols-1 items-center gap-12 px-6 pt-36 pb-20 lg:grid-cols-12 lg:gap-14" aria-labelledby="hero-title">
                <div class="lg:col-span-6">
                    <div data-enter="hero" data-delay="100" class="mb-6 inline-flex items-center gap-2 rounded-full border border-blue-500/20 bg-blue-500/10 px-3 py-1 text-xs font-semibold tracking-[0.12em] text-blue-400">
                        <span class="size-1.5 rounded-full bg-blue-400 shadow-[0_0_12px_rgba(96,165,250,0.9)]"></span>
                        PORTAL RAPOR SEKOLAH
                    </div>

                    <h1 id="hero-title" data-enter="hero" data-delay="180" class="mb-6 text-4xl leading-[1.15] font-extrabold tracking-tight text-white lg:text-5xl">
                        Kelola data akademik, nilai, dan rapor dalam
                        <span class="bg-gradient-to-r from-blue-400 to-cyan-300 bg-clip-text text-transparent">satu sistem terintegrasi.</span>
                    </h1>

                    <p data-enter="hero" data-delay="260" class="mb-8 max-w-2xl text-base leading-relaxed text-slate-400 lg:text-lg">
                        Satu panel untuk administrator, guru, dan siswa. Mulai dari impor data dan penugasan pengajar hingga kenaikan kelas, portal nilai siswa, serta rapor PDF siap cetak.
                    </p>

                    <div data-enter="hero" data-delay="340" class="flex flex-col gap-3 sm:flex-row">
                        @guest
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3.5 font-semibold text-white shadow-lg shadow-blue-500/25 transition-all hover:-translate-y-0.5 hover:bg-blue-500 hover:shadow-blue-500/35 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-400 motion-reduce:transform-none">
                                Masuk ke Sistem
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m9 18 6-6-6-6" />
                                </svg>
                            </a>
                        @else
                            <a href="{{ url('/admin') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3.5 font-semibold text-white shadow-lg shadow-blue-500/25 transition-all hover:-translate-y-0.5 hover:bg-blue-500 hover:shadow-blue-500/35 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-400 motion-reduce:transform-none">
                                Buka Dashboard
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m9 18 6-6-6-6" />
                                </svg>
                            </a>
                        @endguest

                        <a href="#demo" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 px-6 py-3.5 font-medium text-slate-300 transition hover:border-white/20 hover:bg-white/5 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-400">
                            <svg class="size-4 text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m5 3 14 9-14 9V3Z" />
                            </svg>
                            Lihat Tampilan
                        </a>
                    </div>

                </div>

                <div id="demo" data-enter="preview" data-float-preview class="relative scroll-mt-28 lg:col-span-6">
                    <div class="absolute inset-10 -z-10 rounded-full bg-blue-600/20 blur-[120px]" aria-hidden="true"></div>
                    <div class="absolute -top-5 right-7 z-10 hidden items-center gap-2 rounded-full border border-cyan-400/20 bg-slate-900/90 px-3 py-1.5 text-[10px] font-semibold tracking-wide text-cyan-300 shadow-xl backdrop-blur md:flex" aria-hidden="true">
                        <span class="size-1.5 rounded-full bg-cyan-400"></span>
                        ADMIN · GURU · SISWA
                    </div>

                    <div class="relative rounded-2xl border border-white/[0.12] bg-slate-900/60 p-3 shadow-2xl shadow-black/80 backdrop-blur-xl">
                        <div class="relative flex h-9 items-center px-1 pb-3">
                            <div class="flex gap-1.5" aria-hidden="true">
                                <span class="size-2.5 rounded-full bg-red-400"></span>
                                <span class="size-2.5 rounded-full bg-amber-400"></span>
                                <span class="size-2.5 rounded-full bg-emerald-400"></span>
                            </div>
                            <span class="absolute left-1/2 -translate-x-1/2 font-mono text-[10px] tracking-[0.14em] text-slate-500 sm:text-xs">PREVIEW DASHBOARD</span>
                        </div>

                        <div class="overflow-hidden rounded-xl border border-white/10 bg-slate-950 shadow-inner">
                            <img src="{{ asset('ui-concepts/raport-dashboard-concept.png') }}" alt="Pratinjau dashboard Sistem Rapor Digital" class="h-auto w-full" loading="eager" decoding="async">
                        </div>

                        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-white/5 bg-slate-950/80 px-4 py-2 text-xs text-slate-400">
                            <span class="flex items-center gap-2"><span class="size-1.5 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)]"></span> Periode akademik terpantau</span>
                            <span>Rapor PDF & ZIP</span>
                            <span>Admin · Guru · Siswa</span>
                        </div>
                    </div>
                </div>
            </section>

            <section id="fitur" class="scroll-mt-28 border-y border-white/5 bg-slate-900/20 py-24" aria-labelledby="features-title">
                <div data-reveal class="mx-auto max-w-7xl px-6 text-center">
                    <p class="mb-3 text-xs font-semibold tracking-[0.18em] text-blue-400">ALUR KERJA TERPADU</p>
                    <h2 id="features-title" class="text-3xl font-bold tracking-tight text-white sm:text-4xl">Fitur Utama Sistem Rapor</h2>
                    <p class="mx-auto mt-4 max-w-2xl text-sm leading-6 text-slate-400 sm:text-base">Fitur operasional dari penyiapan data sampai siswa melihat nilai yang sudah dibagikan.</p>
                </div>

                <div class="mx-auto mt-12 grid max-w-7xl grid-cols-1 gap-6 px-6 md:grid-cols-2 lg:grid-cols-3">
                    <article data-reveal data-delay="0" class="group rounded-2xl border border-white/10 bg-slate-900/40 p-6 transition-all duration-300 hover:-translate-y-1 hover:border-blue-500/50 hover:shadow-xl hover:shadow-blue-500/10 motion-reduce:transform-none">
                        <div class="mb-4 flex size-12 items-center justify-center rounded-xl border border-blue-500/20 bg-blue-500/10 text-blue-400">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" />
                                <rect width="6" height="4" x="9" y="3" rx="1" />
                                <path d="m9 14 2 2 4-4" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white transition group-hover:text-blue-400">Master Data & Import</h3>
                        <p class="mt-3 text-sm leading-relaxed text-slate-400">Kelola tahun ajaran, kelas, mata pelajaran, guru, dan siswa. Data siswa dapat ditambahkan atau diperbarui lewat Excel dan CSV.</p>
                    </article>

                    <article data-reveal data-delay="100" class="group rounded-2xl border border-white/10 bg-slate-900/40 p-6 transition-all duration-300 hover:-translate-y-1 hover:border-blue-500/50 hover:shadow-xl hover:shadow-blue-500/10 motion-reduce:transform-none">
                        <div class="mb-4 flex size-12 items-center justify-center rounded-xl border border-blue-500/20 bg-blue-500/10 text-blue-400">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 3v18h18" />
                                <path d="m7 16 4-4 4 3 5-7" />
                                <path d="M18 8h2v2" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white transition group-hover:text-blue-400">Penugasan & Input Nilai</h3>
                        <p class="mt-3 text-sm leading-relaxed text-slate-400">Tetapkan guru per kelas dan mata pelajaran, lalu input nilai siswa sesuai penugasan dengan validasi dan penyimpanan batch.</p>
                    </article>

                    <article data-reveal data-delay="200" class="group rounded-2xl border border-white/10 bg-slate-900/40 p-6 transition-all duration-300 hover:-translate-y-1 hover:border-blue-500/50 hover:shadow-xl hover:shadow-blue-500/10 motion-reduce:transform-none">
                        <div class="mb-4 flex size-12 items-center justify-center rounded-xl border border-blue-500/20 bg-blue-500/10 text-blue-400">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M6 9V2h12v7" />
                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                                <rect width="12" height="8" x="6" y="14" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white transition group-hover:text-blue-400">Progres & Rapor PDF</h3>
                        <p class="mt-3 text-sm leading-relaxed text-slate-400">Pantau kelengkapan nilai, isi saran rapor, pratinjau hasil, lalu unduh PDF per siswa atau ZIP untuk banyak siswa sekaligus.</p>
                    </article>

                    <article data-reveal data-delay="0" class="group rounded-2xl border border-white/10 bg-slate-900/40 p-6 transition-all duration-300 hover:-translate-y-1 hover:border-blue-500/50 hover:shadow-xl hover:shadow-blue-500/10 motion-reduce:transform-none">
                        <div class="mb-4 flex size-12 items-center justify-center rounded-xl border border-blue-500/20 bg-blue-500/10 text-blue-400">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" /><path d="m9 16 2 2 4-4" /></svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white transition group-hover:text-blue-400">Kenaikan & Kelulusan</h3>
                        <p class="mt-3 text-sm leading-relaxed text-slate-400">Proses kenaikan kelas dan kelulusan dengan riwayat kelas tetap tersimpan. Siswa lulus otomatis masuk pengelolaan data alumni.</p>
                    </article>

                    <article data-reveal data-delay="100" class="group rounded-2xl border border-white/10 bg-slate-900/40 p-6 transition-all duration-300 hover:-translate-y-1 hover:border-blue-500/50 hover:shadow-xl hover:shadow-blue-500/10 motion-reduce:transform-none">
                        <div class="mb-4 flex size-12 items-center justify-center rounded-xl border border-blue-500/20 bg-blue-500/10 text-blue-400">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 7h-9M14 17H5" /><circle cx="17" cy="17" r="3" /><circle cx="7" cy="7" r="3" /></svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white transition group-hover:text-blue-400">Portal Nilai Siswa</h3>
                        <p class="mt-3 text-sm leading-relaxed text-slate-400">Administrator mengatur akses portal. Siswa hanya melihat nilai final yang sudah dibagikan sesuai akun dan hak aksesnya.</p>
                    </article>

                    <article data-reveal data-delay="200" class="group rounded-2xl border border-white/10 bg-slate-900/40 p-6 transition-all duration-300 hover:-translate-y-1 hover:border-blue-500/50 hover:shadow-xl hover:shadow-blue-500/10 motion-reduce:transform-none">
                        <div class="mb-4 flex size-12 items-center justify-center rounded-xl border border-blue-500/20 bg-blue-500/10 text-blue-400">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" /><path d="M10 21h4" /></svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white transition group-hover:text-blue-400">Notifikasi & Akun</h3>
                        <p class="mt-3 text-sm leading-relaxed text-slate-400">Pisahkan notifikasi belum dibaca dan semua notifikasi, kelola status baca, profil, foto, kata sandi, serta pemulihan akses akun.</p>
                    </article>
                </div>
            </section>

            <section id="keunggulan" class="scroll-mt-28 py-24" aria-labelledby="advantages-title">
                <div class="mx-auto grid max-w-7xl gap-12 px-6 lg:grid-cols-2 lg:items-center">
                    <div data-reveal>
                        <p class="mb-3 text-xs font-semibold tracking-[0.18em] text-blue-400">DIBUAT UNTUK SEKOLAH</p>
                        <h2 id="advantages-title" class="text-3xl font-bold tracking-tight text-white sm:text-4xl">Lebih sedikit pekerjaan berulang. Lebih banyak kendali.</h2>
                        <p class="mt-5 max-w-xl leading-relaxed text-slate-400">Setiap peran mendapatkan ruang kerja sesuai tanggung jawabnya, sehingga data tetap teratur dari awal semester sampai rapor diterbitkan.</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ([
                            ['Data terpusat', 'Identitas, penugasan, nilai, riwayat kelas, dan rapor tersimpan dalam satu alur.', 'database'],
                            ['Akses berbasis peran', 'Administrator, guru, dan siswa mendapat menu serta data sesuai kewenangannya.', 'users'],
                            ['Status transparan', 'Dashboard memperlihatkan progres nilai dan kesiapan rapor pada periode aktif.', 'pulse'],
                            ['Siap distribusi', 'Rapor final dapat dipratinjau, diunduh sebagai PDF, atau dikemas massal dalam ZIP.', 'document'],
                        ] as [$title, $description, $icon])
                            <div data-reveal data-delay="{{ $loop->index * 80 }}" class="rounded-xl border border-white/[0.08] bg-white/[0.025] p-5">
                                <div class="mb-4 flex size-9 items-center justify-center rounded-lg bg-blue-500/10 text-blue-400">
                                    @if ($icon === 'database')
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><ellipse cx="12" cy="5" rx="8" ry="3" /><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5" /><path d="M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6" /></svg>
                                    @elseif ($icon === 'users')
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" /></svg>
                                    @elseif ($icon === 'pulse')
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12h4l2-8 4 16 2-8h6" /></svg>
                                    @else
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" /><path d="M14 2v6h6M8 13h8M8 17h5" /></svg>
                                    @endif
                                </div>
                                <h3 class="font-semibold text-white">{{ $title }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-slate-400">{{ $description }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="faq" class="scroll-mt-28 border-t border-white/5 bg-slate-900/20 py-24" aria-labelledby="faq-title">
                <div class="mx-auto max-w-3xl px-6">
                    <div data-reveal class="text-center">
                        <p class="mb-3 text-xs font-semibold tracking-[0.18em] text-blue-400">PERTANYAAN UMUM</p>
                        <h2 id="faq-title" class="text-3xl font-bold tracking-tight text-white sm:text-4xl">Hal yang perlu diketahui</h2>
                    </div>

                    <div data-reveal data-delay="100" class="mt-10 divide-y divide-white/10 border-y border-white/10">
                        <details class="group py-5">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium text-slate-200 marker:hidden">
                                Siapa yang dapat menggunakan sistem?
                                <svg class="size-5 shrink-0 text-slate-500 transition group-open:rotate-45" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                            </summary>
                            <p class="pt-3 pr-10 text-sm leading-relaxed text-slate-400">Sistem menyediakan akses berbasis peran untuk administrator sekolah, guru, dan siswa. Guru hanya mengelola kelas serta mata pelajaran yang ditugaskan.</p>
                        </details>
                        <details class="group py-5">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium text-slate-200 marker:hidden">
                                Apakah rapor dapat dicetak sebagai PDF?
                                <svg class="size-5 shrink-0 text-slate-500 transition group-open:rotate-45" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                            </summary>
                            <p class="pt-3 pr-10 text-sm leading-relaxed text-slate-400">Ya. Rapor yang nilainya sudah lengkap dapat dipratinjau dan diunduh sebagai PDF. Beberapa rapor siap cetak juga dapat diunduh sekaligus dalam berkas ZIP.</p>
                        </details>
                        <details class="group py-5">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium text-slate-200 marker:hidden">
                                Apakah data siswa harus dimasukkan satu per satu?
                                <svg class="size-5 shrink-0 text-slate-500 transition group-open:rotate-45" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                            </summary>
                            <p class="pt-3 pr-10 text-sm leading-relaxed text-slate-400">Tidak. Administrator dapat mengimpor data siswa dari template Excel atau CSV. Sistem menambah dan memperbarui data berdasarkan NISN.</p>
                        </details>
                        <details class="group py-5">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium text-slate-200 marker:hidden">
                                Bagaimana siswa melihat nilainya?
                                <svg class="size-5 shrink-0 text-slate-500 transition group-open:rotate-45" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                            </summary>
                            <p class="pt-3 pr-10 text-sm leading-relaxed text-slate-400">Siswa masuk memakai akun portal dan hanya dapat melihat nilai final setelah administrator mengaktifkan akses nilainya.</p>
                        </details>
                    </div>
                </div>
            </section>

            <section class="px-6 py-20">
                <div data-reveal class="relative mx-auto max-w-5xl overflow-hidden rounded-3xl border border-blue-400/15 bg-blue-600/10 px-6 py-14 text-center shadow-2xl shadow-blue-950/30 sm:px-12">
                    <div class="absolute inset-x-1/4 -top-24 h-48 rounded-full bg-blue-500/30 blur-[100px]" aria-hidden="true"></div>
                    <div class="relative">
                        <p class="mb-3 text-xs font-semibold tracking-[0.18em] text-cyan-300">SATU ALUR AKADEMIK</p>
                        <h2 class="text-3xl font-bold tracking-tight text-white">Siapkan data, tuntaskan nilai, lalu terbitkan rapor.</h2>
                        <p class="mx-auto mt-4 max-w-2xl text-sm leading-relaxed text-slate-300">Kelola seluruh periode akademik melalui panel responsif dengan mode terang dan gelap, notifikasi terstruktur, serta akses aman berbasis peran.</p>
                        <a href="{{ auth()->check() ? url('/admin') : route('login') }}" class="mt-7 inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white shadow-lg shadow-blue-500/25 transition hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-400">
                            {{ auth()->check() ? 'Buka Dashboard' : 'Masuk ke Sistem' }}
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-white/5 px-6 py-8">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 text-center text-xs text-slate-500 sm:flex-row sm:text-left">
                <p>&copy; {{ now()->year }} Sistem Rapor Digital. Data akademik, nilai, dan rapor dalam satu alur.</p>
                <a href="{{ url('/') }}" class="transition hover:text-slate-300 focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-500">Kembali ke atas</a>
            </div>
        </footer>

        <script>
            (() => {
                const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                if (reducedMotion) {
                    return;
                }

                const easing = 'cubic-bezier(0.22, 1, 0.36, 1)';

                document.querySelector('[data-enter="navbar"]')?.animate(
                    [
                        { opacity: 0, transform: 'translateY(-18px) scale(0.98)' },
                        { opacity: 1, transform: 'translateY(0) scale(1)' },
                    ],
                    { duration: 700, easing, fill: 'both' },
                );

                document.querySelectorAll('[data-enter="hero"]').forEach((element) => {
                    element.animate(
                        [
                            { opacity: 0, transform: 'translateY(24px)' },
                            { opacity: 1, transform: 'translateY(0)' },
                        ],
                        { duration: 750, delay: Number(element.dataset.delay || 0), easing, fill: 'both' },
                    );
                });

                document.querySelector('[data-enter="preview"]')?.animate(
                    [
                        { opacity: 0, transform: 'translateY(30px) scale(0.96)' },
                        { opacity: 1, transform: 'translateY(0) scale(1)' },
                    ],
                    { duration: 900, delay: 260, easing, fill: 'both' },
                );

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) {
                            return;
                        }

                        entry.target.animate(
                            [
                                { opacity: 0, transform: 'translateY(28px)' },
                                { opacity: 1, transform: 'translateY(0)' },
                            ],
                            {
                                duration: 700,
                                delay: Number(entry.target.dataset.delay || 0),
                                easing,
                                fill: 'both',
                            },
                        );
                        observer.unobserve(entry.target);
                    });
                }, { threshold: 0.14, rootMargin: '0px 0px -48px' });

                document.querySelectorAll('[data-reveal]').forEach((element) => observer.observe(element));

                document.querySelector('[data-ambient="left"]')?.animate(
                    [
                        { transform: 'translate3d(0, 0, 0) scale(1)', opacity: 0.75 },
                        { transform: 'translate3d(70px, 35px, 0) scale(1.12)', opacity: 1 },
                        { transform: 'translate3d(0, 0, 0) scale(1)', opacity: 0.75 },
                    ],
                    { duration: 14000, iterations: Infinity, easing: 'ease-in-out' },
                );

                document.querySelector('[data-ambient="right"]')?.animate(
                    [
                        { transform: 'translate3d(0, 0, 0) scale(1)' },
                        { transform: 'translate3d(-55px, 45px, 0) scale(1.15)' },
                        { transform: 'translate3d(0, 0, 0) scale(1)' },
                    ],
                    { duration: 17000, iterations: Infinity, easing: 'ease-in-out' },
                );

                document.querySelector('[data-float-preview]')?.animate(
                    [
                        { translate: '0 0' },
                        { translate: '0 -7px' },
                        { translate: '0 0' },
                    ],
                    { duration: 6000, delay: 1200, iterations: Infinity, easing: 'ease-in-out' },
                );
            })();
        </script>
    </body>
</html>

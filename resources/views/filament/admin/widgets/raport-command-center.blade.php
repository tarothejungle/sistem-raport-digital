<x-filament-widgets::widget>
    @if ($isGuruDashboard ?? false)
        <div class="raport-guru-dashboard h-auto w-full pb-12 md:pb-6">
            <div class="raport-guru-profile-grid grid h-auto w-full grid-cols-1 gap-4 md:grid-cols-2 lg:gap-6">
                <section class="raport-guru-card raport-guru-profile-card rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-slate-900/70 dark:shadow-2xl" aria-labelledby="guru-profile-heading">
                    <div class="raport-guru-profile-card__main">
                        <div class="raport-guru-avatar">
                            @if ($guruProfile['avatarUrl'])
                                <img src="{{ $guruProfile['avatarUrl'] }}" alt="Foto profil {{ $guruProfile['name'] }}">
                            @else
                                <span aria-hidden="true">{{ str($guruProfile['name'])->substr(0, 1)->upper() }}</span>
                                <span class="sr-only">{{ $guruProfile['name'] }}</span>
                            @endif
                        </div>

                        <div class="raport-guru-profile-card__identity">
                            <h3 id="guru-profile-heading">{{ $guruProfile['name'] }}</h3>
                            <div class="raport-guru-profile-card__badges">
                                <span class="raport-guru-role-badge">{{ $guruProfile['role'] }}</span>
                                <span class="raport-guru-wali-text">
                                    {{ $guruProfile['waliKelas'] ? 'Wali '.$guruProfile['waliKelas'] : 'Belum menjadi wali kelas' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="raport-guru-profile-card__actions">
                        <a href="{{ $guruProfile['editProfileUrl'] }}" class="raport-btn-primary">
                            Ubah profil
                        </a>
                        @if ($guruProfile['avatarUrl'])
                            {{ $this->deleteAvatarAction }}
                        @else
                            <span class="raport-btn-ghost-disabled">
                                Belum ada foto
                            </span>
                        @endif
                    </div>
                </section>

                <section class="raport-guru-card raport-guru-detail-card rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-slate-900/70 dark:shadow-2xl" aria-labelledby="guru-detail-heading">
                    <div class="raport-guru-detail-card__title">
                        <span class="raport-guru-detail-card__icon" aria-hidden="true">
                            <x-filament::icon icon="heroicon-o-identification" class="h-5 w-5" />
                        </span>
                        <h3 id="guru-detail-heading">Data diri</h3>
                    </div>

                    <dl class="raport-guru-details">
                        @foreach ($guruDetails as $detail)
                            <div class="raport-guru-detail-row">
                                <dt>
                                    <x-filament::icon :icon="$detail['icon']" class="h-4 w-4" aria-hidden="true" />
                                    {{ $detail['label'] }}
                                </dt>
                                <dd>{{ $detail['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            </div>

            <section class="raport-guru-card raport-guru-teaching-card rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-slate-900/70 dark:shadow-2xl" aria-labelledby="guru-teaching-heading">
                <div class="raport-guru-teaching-card__header">
                    <div>
                        <span class="raport-eyebrow">Prioritas Anda</span>
                        <h3 id="guru-teaching-heading">Kelas dan Mata Pelajaran</h3>
                        <p>Lanjutkan pengisian nilai dan periksa status pengiriman.</p>
                    </div>
                </div>

                @if (count($guruTeachingRows) > 0)
                    <div class="raport-guru-table-wrap">
                        <table class="raport-guru-table">
                            <thead>
                                <tr>
                                    <th>Kelas</th>
                                    <th>Mata Pelajaran</th>
                                    <th>Pengisian</th>
                                    <th>Pengiriman</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($guruTeachingRows as $row)
                                    <tr>
                                        <td>
                                            <span class="raport-guru-table__kelas">{{ $row['kelas'] }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ $row['inputUrl'] }}" class="raport-guru-table__mapel-link">{{ $row['mapel'] }}</a>
                                            <span class="raport-guru-table__meta">{{ $row['submittedCount'] }} dari {{ $row['studentCount'] }} siswa</span>
                                        </td>
                                        <td>
                                            <span class="raport-guru-status {{ $row['isFilled'] ? 'is-complete' : 'is-pending' }}">
                                                <x-filament::icon :icon="$row['isFilled'] ? 'heroicon-o-check-circle' : 'heroicon-o-clock'" class="h-4 w-4" />
                                                {{ $row['isFilled'] ? 'Lengkap' : 'Perlu diisi' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="raport-guru-status {{ $row['isSubmitted'] ? 'is-complete' : 'is-pending' }}">
                                                <x-filament::icon :icon="$row['isSubmitted'] ? 'heroicon-o-check-circle' : 'heroicon-o-clock'" class="h-4 w-4" />
                                                {{ $row['isSubmitted'] ? 'Terkirim' : 'Belum terkirim' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="raport-guru-mobile-list" aria-label="Daftar penugasan mengajar">
                        @foreach ($guruTeachingRows as $row)
                            <article class="raport-guru-mobile-item">
                                <div class="raport-guru-mobile-item__top">
                                    <div class="raport-guru-mobile-item__identity">
                                        <span class="raport-guru-mobile-item__kelas">Kelas {{ $row['kelas'] }}</span>
                                        <a href="{{ $row['inputUrl'] }}" class="raport-guru-mobile-item__mapel">
                                            {{ $row['mapel'] }}
                                        </a>
                                    </div>
                                    <span class="raport-guru-status raport-guru-status--compact {{ $row['isFilled'] ? 'is-complete' : 'is-pending' }}">
                                        {{ $row['isFilled'] ? 'Lengkap' : 'Perlu diisi' }}
                                    </span>
                                </div>

                                <div class="raport-guru-mobile-item__bottom">
                                    <span class="raport-guru-mobile-item__progress">
                                        {{ $row['submittedCount'] }} dari {{ $row['studentCount'] }} siswa
                                    </span>
                                    <span class="raport-guru-mobile-item__shipment {{ $row['isSubmitted'] ? 'is-complete' : 'is-pending' }}">
                                        <x-filament::icon :icon="$row['isSubmitted'] ? 'heroicon-o-check-circle' : 'heroicon-o-clock'" class="h-4 w-4" />
                                        {{ $row['isSubmitted'] ? 'Terkirim' : 'Belum terkirim' }}
                                    </span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="raport-empty-state">
                        <x-filament::icon icon="heroicon-o-book-open" class="h-8 w-8" />
                        <p>Belum ada mata pelajaran yang diampu pada periode aktif.</p>
                    </div>
                @endif
            </section>

        </div>

    @else
         <div class="raport-dashboard-stack h-auto w-full overflow-visible pb-12 md:pb-6">
        <section class="raport-dashboard-hero raport-readiness-card flex flex-col items-stretch justify-between gap-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:flex-row lg:items-center dark:border-white/10 dark:bg-slate-900/70 dark:shadow-2xl">
            <div class="raport-dashboard-hero__copy">
                <span class="raport-eyebrow">{{ $roleLabel }}</span>
                <h2 class="raport-dashboard-hero__title font-bold text-slate-900 dark:text-white">{{ $heroTitle }}</h2>
                <p class="raport-dashboard-hero__description text-slate-600 dark:text-slate-400">
                    {{ $heroDescription }}
                </p>

                <div class="raport-dashboard-hero__chips mt-4 flex flex-wrap gap-2">
                    <span class="raport-dashboard-chip bg-slate-50 text-slate-800 dark:bg-slate-950/80 dark:text-slate-200">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="h-4 w-4" />
                        Periode berjalan
                    </span>
                    <a href="#raport-quick-actions" class="raport-dashboard-chip bg-slate-50 text-slate-800 dark:bg-slate-950/80 dark:text-slate-200">
                        <x-filament::icon icon="heroicon-o-bolt" class="h-4 w-4" />
                        Aksi cepat · {{ $totalActions }}
                    </a>
                </div>
            </div>

            <div class="raport-dashboard-hero__visual flex w-full flex-col items-center justify-center rounded-2xl border border-slate-200 bg-slate-100/80 p-6 lg:w-auto dark:border-white/10 dark:bg-slate-950/60">
                @if ($showProgressPanel)
                    <div
                        class="raport-progress-ring"
                        style="--progress: {{ $averageProgress }}%;"
                        aria-label="Rata-rata kemajuan {{ $averageProgress }} persen"
                    >
                        <span class="text-slate-900 dark:text-white">{{ $averageProgress }}%</span>
                        <small>Kemajuan</small>
                    </div>
                @endif

                <div class="raport-dashboard-hero__metrics">
                    <div class="raport-mini-metric border border-slate-200 bg-white dark:border-white/5 dark:bg-slate-950/60">
                        <span class="raport-mini-metric__label">Periode</span>
                        <span class="raport-mini-metric__value">{{ $periodeLabel }}</span>
                    </div>

                    @if ($showProgressPanel)
                        <div class="raport-mini-metric border border-slate-200 bg-white dark:border-white/5 dark:bg-slate-950/60">
                            <span class="raport-mini-metric__label">Kelas Siap</span>
                            <span class="raport-mini-metric__value">{{ $kelasSiap }} / {{ $totalKelas }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <div class="raport-command-center grid h-auto w-full grid-cols-1 gap-4 overflow-visible lg:gap-6 @unless ($showProgressPanel) raport-command-center--single @endunless">
            @if ($showProgressPanel)
            <section class="raport-panel raport-panel--progress rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-slate-900/70 dark:shadow-2xl">
                <div class="raport-panel__header">
                    <div>
                        <h2 class="raport-panel__title">Kemajuan Rapor per Kelas</h2>
                        <p class="raport-panel__caption">{{ $periodeLabel }}</p>
                    </div>

                    <span class="raport-panel__badge">
                        {{ $kelasSiap }} siap
                    </span>
                </div>

                @if (count($progressRows) > 0)
                    <div class="raport-table-scroll">
                        <table class="raport-progress-table">
                            <thead>
                                <tr>
                                    <th>Kelas</th>
                                    <th>Wali Kelas</th>
                                    <th>Siswa</th>
                                    <th>Kemajuan Nilai</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($progressRows as $row)
                                    <tr>
                                        <td>
                                            <span class="raport-table-title">{{ $row['kelas'] }}</span>
                                        </td>
                                        <td>{{ $row['waliKelas'] }}</td>
                                        <td>{{ $row['siswa'] }}</td>
                                        <td>
                                            <div class="raport-progress">
                                                <span>{{ $row['progress'] }}%</span>
                                                <span class="raport-progress__track" aria-hidden="true">
                                                    <span
                                                        class="raport-progress__bar"
                                                        style="width: {{ $row['progress'] }}%"
                                                    ></span>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="raport-status raport-status--{{ $row['statusTone'] }}">
                                                {{ $row['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="raport-progress-cards" aria-label="Kemajuan rapor per kelas">
                        @foreach ($progressRows as $row)
                            <article class="raport-progress-card">
                                <div class="raport-progress-card__header">
                                    <div>
                                        <strong>{{ $row['kelas'] }}</strong>
                                        <span>{{ $row['waliKelas'] }}</span>
                                    </div>
                                    <span class="raport-status raport-status--{{ $row['statusTone'] }}">{{ $row['status'] }}</span>
                                </div>
                                <div class="raport-progress-card__meta">
                                    <span>{{ $row['siswa'] }} siswa</span>
                                    <strong>{{ $row['progress'] }}%</strong>
                                </div>
                                <span class="raport-progress__track" aria-hidden="true">
                                    <span class="raport-progress__bar" style="width: {{ $row['progress'] }}%"></span>
                                </span>
                            </article>
                        @endforeach
                    </div>
                @else
                    <p class="raport-empty">
                        Belum ada kelas yang bisa ditampilkan untuk akun ini.
                    </p>
                @endif
            </section>
            @endif

            <section id="raport-quick-actions" class="raport-panel scroll-mt-24 rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-slate-900/70 dark:shadow-2xl">
                <div class="raport-panel__header">
                    <div>
                        <h2 class="raport-panel__title">Aksi Cepat</h2>
                        <p class="raport-panel__caption">Alur yang paling sering dipakai.</p>
                    </div>
                </div>

                <div class="raport-actions grid h-auto w-full grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-1 lg:gap-6">
                    @foreach ($actions as $action)
                        @if ($action['url'])
                            <a
                                class="raport-action raport-action--{{ $action['tone'] }}"
                                href="{{ $action['url'] }}"
                            >
                                <span class="raport-action__icon">
                                    <x-filament::icon :icon="$action['icon']" class="h-6 w-6" />
                                </span>
                                <span>
                                    <span class="raport-action__title">{{ $action['title'] }}</span>
                                    <span class="raport-action__description">{{ $action['description'] }}</span>
                                </span>
                                <x-filament::icon
                                    icon="heroicon-m-chevron-right"
                                    class="raport-action__arrow h-5 w-5"
                                />
                            </a>
                        @else
                            <div
                                class="raport-action raport-action--{{ $action['tone'] }}"
                                aria-disabled="true"
                            >
                                <span class="raport-action__icon">
                                    <x-filament::icon :icon="$action['icon']" class="h-6 w-6" />
                                </span>
                                <span>
                                    <span class="raport-action__title">{{ $action['title'] }}</span>
                                    <span class="raport-action__description">{{ $action['description'] }}</span>
                                </span>
                                <x-filament::icon
                                    icon="heroicon-m-lock-closed"
                                    class="raport-action__arrow h-5 w-5"
                                />
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>
        </div>
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament-widgets::widget>

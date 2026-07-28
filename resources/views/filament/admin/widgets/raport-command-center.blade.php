<x-filament-widgets::widget>
    @if ($isGuruDashboard ?? false)
        <div class="raport-guru-dashboard">
            <div class="raport-guru-profile-grid">
                <section class="raport-guru-card raport-guru-profile-card" aria-labelledby="guru-profile-heading">
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
                        <a href="{{ $guruProfile['editProfileUrl'] }}" wire:navigate class="raport-btn-primary">
                            Ubah profil
                        </a>
                        @if ($guruProfile['avatarUrl'])
                            <button
                                type="button"
                                class="raport-btn-danger-outline"
                                wire:click="$dispatch('openDeleteAvatarModal')"
                            >
                                Hapus foto
                            </button>
                        @else
                            <span class="raport-btn-ghost-disabled">
                                Belum ada foto
                            </span>
                        @endif
                    </div>
                </section>

                <section class="raport-guru-card raport-guru-detail-card" aria-labelledby="guru-detail-heading">
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

            <section class="raport-guru-card raport-guru-teaching-card" aria-labelledby="guru-teaching-heading">
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
                                            <a href="{{ $row['inputUrl'] }}" wire:navigate class="raport-guru-table__mapel-link">{{ $row['mapel'] }}</a>
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
                                        <a href="{{ $row['inputUrl'] }}" wire:navigate class="raport-guru-mobile-item__mapel">
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

        <livewire:delete-avatar-modal />

    @else
        <div class="raport-dashboard-stack">
        <section class="raport-dashboard-hero">
            <div class="raport-dashboard-hero__copy">
                <span class="raport-eyebrow">{{ $roleLabel }}</span>
                <h2 class="raport-dashboard-hero__title">Status Kesiapan Rapor</h2>
                <p class="raport-dashboard-hero__description">
                    Pantau kelengkapan nilai, akses alur kerja utama, dan siapkan rapor pada periode aktif.
                </p>

                <div class="raport-dashboard-hero__chips">
                    <span>
                        <x-filament::icon icon="heroicon-o-calendar-days" class="h-4 w-4" />
                        Periode berjalan
                    </span>
                    <span>
                        <x-filament::icon icon="heroicon-o-bolt" class="h-4 w-4" />
                        {{ $totalActions }} aksi cepat
                    </span>
                </div>
            </div>

            <div class="raport-dashboard-hero__visual">
                <div
                    class="raport-progress-ring"
                    style="--progress: {{ $averageProgress }}%;"
                    aria-label="Rata-rata kemajuan {{ $averageProgress }} persen"
                >
                    <span>{{ $averageProgress }}%</span>
                    <small>Kemajuan</small>
                </div>

                <div class="raport-dashboard-hero__metrics">
                    <div class="raport-mini-metric">
                        <span class="raport-mini-metric__label">Periode</span>
                        <span class="raport-mini-metric__value">{{ $periodeLabel }}</span>
                    </div>

                    <div class="raport-mini-metric">
                        <span class="raport-mini-metric__label">Kelas Siap</span>
                        <span class="raport-mini-metric__value">{{ $kelasSiap }} / {{ $totalKelas }}</span>
                    </div>
                </div>
            </div>
        </section>

        <div class="raport-command-center">
            <section class="raport-panel raport-panel--progress">
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

            <section class="raport-panel">
                <div class="raport-panel__header">
                    <div>
                        <h2 class="raport-panel__title">Aksi Cepat</h2>
                        <p class="raport-panel__caption">Alur yang paling sering dipakai.</p>
                    </div>
                </div>

                <div class="raport-actions">
                    @foreach ($actions as $action)
                        @if ($action['url'])
                            <a
                                class="raport-action raport-action--{{ $action['tone'] }}"
                                href="{{ $action['url'] }}"
                                wire:navigate
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
</x-filament-widgets::widget>

<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Pages\Auth\EditProfile;
use App\Filament\Admin\Pages\CetakRaporSiswa;
use App\Filament\Admin\Pages\InputNilaiPerMapel;
use App\Filament\Admin\Resources\NilaiResource;
use App\Filament\Admin\Resources\NilaiSiswaResource;
use App\Filament\Admin\Resources\SiswaResource;
use App\Models\JadwalMengajar;
use App\Models\Kelas;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;

class RaportCommandCenter extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected string $view = 'filament.admin.widgets.raport-command-center';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    /**
     * Rendered for every authenticated panel user; the view data itself is
     * scoped per role in getViewData().
     */
    public static function canView(): bool
    {
        return auth()->check();
    }

    /**
     * The avatar lives in the guru profile card, so the card has to re-render
     * once the delete action removes it.
     */
    #[On('avatarDeleted')]
    public function refreshAfterAvatarDeleted(): void
    {
        // The attribute alone triggers a re-render; no state to reset.
    }

    public function deleteAvatar(): void
    {
        $user = auth()->user();

        if ($user === null || blank($user->avatar_path)) {
            return;
        }

        Storage::disk('public')->delete($user->avatar_path);
        $user->update(['avatar_path' => null]);
        $user->refresh();

        Notification::make()
            ->success()
            ->title('Foto profil berhasil dihapus.')
            ->send();

        $this->dispatch('avatarDeleted');
        $this->dispatch('profile-avatar-updated', url: Filament::getUserAvatarUrl($user));
    }

    public function deleteAvatarAction(): Action
    {
        return Action::make('deleteAvatar')
            ->label('Hapus foto')
            ->color('danger')
            ->extraAttributes(['class' => 'raport-delete-avatar-action'])
            ->requiresConfirmation()
            ->modalHeading('Hapus foto profil?')
            ->modalDescription('Foto akan dihapus dari akun dan avatar akan kembali memakai inisial nama. Tindakan ini tidak dapat dibatalkan.')
            ->modalSubmitActionLabel('Ya, hapus foto')
            ->modalCancelActionLabel('Batal')
            ->action(function (): void {
                $this->deleteAvatar();
            });
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        if (auth()->user()?->isGuru()) {
            return [
                'isGuruDashboard' => true,
                ...$this->guruDashboardData(),
            ];
        }

        $isSiswa = auth()->user()?->isSiswa() ?? false;

        $tahunAjaran = TahunAjaran::query()
            ->where('is_active', true)
            ->first();

        $actions = $this->quickActions();
        $progressRows = $this->progressRows($tahunAjaran);
        $totalKelas = count($progressRows);
        $kelasSiap = collect($progressRows)
            ->where('statusTone', 'success')
            ->count();

        return [
            'isGuruDashboard' => false,
            'actions' => $actions,
            'averageProgress' => $totalKelas > 0
                ? (int) round(collect($progressRows)->avg('progress'))
                : 0,
            'heroDescription' => $isSiswa
                ? 'Lihat nilai yang sudah dibagikan dan informasi periode akademik Anda.'
                : 'Pantau kelengkapan nilai, akses alur kerja utama, dan siapkan rapor pada periode aktif.',
            'heroTitle' => $isSiswa ? 'Ringkasan Akademik Saya' : 'Status Kesiapan Rapor',
            'kelasSiap' => $kelasSiap,
            'progressRows' => $progressRows,
            'periodeLabel' => $tahunAjaran?->label ?? 'Belum ada tahun ajaran aktif',
            'roleLabel' => $this->roleLabel(),
            'showProgressPanel' => ! $isSiswa,
            'totalActions' => count($actions),
            'totalKelas' => $totalKelas,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function guruDashboardData(): array
    {
        $user = auth()->user();
        $guru = $user?->guru;
        $tahunAjaran = TahunAjaran::query()
            ->where('is_active', true)
            ->first();

        $waliKelas = $guru === null
            ? null
            : Kelas::query()
                ->where('wali_kelas_id', $guru->getKey())
                ->orderBy('tingkat')
                ->orderBy('nama_kelas')
                ->first();

        $jadwalRows = $guru === null
            ? []
            : $this->guruTeachingRows($guru->getKey(), $tahunAjaran);

        return [
            'guruProfile' => [
                'name' => $user?->name ?? '-',
                'role' => 'Guru',
                'waliKelas' => $waliKelas?->nama_kelas,
                'avatarUrl' => $user?->getFilamentAvatarUrl(),
                'editProfileUrl' => EditProfile::getUrl(),
            ],
            'guruDetails' => [
                ['icon' => 'heroicon-o-user', 'label' => 'Username', 'value' => $user?->username ?? '-'],
                ['icon' => 'heroicon-o-identification', 'label' => 'Jenis Kelamin', 'value' => match ($guru?->jenis_kelamin) {
                    'L' => 'Laki-laki',
                    'P' => 'Perempuan',
                    default => '-',
                }],
                ['icon' => 'heroicon-o-calendar-days', 'label' => 'Tempat, Tanggal Lahir', 'value' => $this->birthPlaceDate($guru)],
                ['icon' => 'heroicon-o-envelope', 'label' => 'Email', 'value' => $user?->email ?? '-'],
                ['icon' => 'heroicon-o-device-phone-mobile', 'label' => 'No. Handphone', 'value' => $guru?->no_telp ?: '-'],
                ['icon' => 'heroicon-o-academic-cap', 'label' => 'Pendidikan Terakhir', 'value' => $guru?->pendidikan_terakhir ?: '-'],
                ['icon' => 'heroicon-o-users', 'label' => 'Jumlah Siswa di Ajar', 'value' => (string) $this->guruStudentCount($guru?->getKey(), $tahunAjaran)],
            ],
            'guruTeachingRows' => $jadwalRows,
            'periodeLabel' => $tahunAjaran?->label ?? 'Belum ada tahun ajaran aktif',
        ];
    }

    private function birthPlaceDate($guru): string
    {
        $parts = collect([
            $guru?->tempat_lahir,
            $guru?->tanggal_lahir?->translatedFormat('d F Y'),
        ])->filter()->values();

        return $parts->isEmpty() ? '-' : $parts->implode(', ');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function guruTeachingRows(int $guruId, ?TahunAjaran $tahunAjaran): array
    {
        return JadwalMengajar::query()
            ->with(['kelas', 'mataPelajaran'])
            ->where('guru_id', $guruId)
            ->when(
                $tahunAjaran !== null,
                static fn (Builder $query): Builder => $query->where('tahun_ajaran_id', $tahunAjaran->getKey()),
            )
            ->orderBy('kelas_id')
            ->orderBy('mapel_id')
            ->get()
            ->map(function (JadwalMengajar $jadwal): array {
                $studentCount = Siswa::query()
                    ->aktif()
                    ->where('kelas_id', $jadwal->kelas_id)
                    ->count();

                $submittedCount = Nilai::query()
                    ->where('jadwal_mengajar_id', $jadwal->getKey())
                    ->where('is_submitted', true)
                    ->count();

                $isComplete = $studentCount > 0 && $submittedCount >= $studentCount;

                return [
                    'kelas' => $jadwal->kelas?->nama_kelas ?? '-',
                    'mapel' => $jadwal->mataPelajaran?->nama_mapel ?? '-',
                    'studentCount' => $studentCount,
                    'submittedCount' => $submittedCount,
                    'isFilled' => $isComplete,
                    'isSubmitted' => $isComplete,
                    'inputUrl' => InputNilaiPerMapel::getUrl(['jadwalMengajar' => $jadwal]),
                ];
            })
            ->values()
            ->all();
    }

    private function guruStudentCount(?int $guruId, ?TahunAjaran $tahunAjaran): int
    {
        if ($guruId === null) {
            return 0;
        }

        $kelasIds = JadwalMengajar::query()
            ->where('guru_id', $guruId)
            ->when(
                $tahunAjaran !== null,
                static fn (Builder $query): Builder => $query->where('tahun_ajaran_id', $tahunAjaran->getKey()),
            )
            ->pluck('kelas_id')
            ->unique()
            ->values();

        if ($kelasIds->isEmpty()) {
            return 0;
        }

        return Siswa::query()
            ->aktif()
            ->whereIn('kelas_id', $kelasIds)
            ->count();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function quickActions(): array
    {
        $user = auth()->user();
        $actions = [];

        if ($user?->isSiswa()) {
            $actions[] = [
                'title' => 'Nilai Saya',
                'description' => 'Lihat nilai final yang sudah dibagikan.',
                'icon' => 'heroicon-o-document-text',
                'url' => NilaiSiswaResource::canViewAny()
                    ? NilaiSiswaResource::getUrl('index')
                    : null,
                'tone' => 'info',
            ];

            return $actions;
        }

        $jadwalInput = $this->firstInputSchedule();

        $actions[] = [
            'title' => 'Input Nilai',
            'description' => $jadwalInput === null
                ? 'Belum ada penugasan yang bisa diinput.'
                : 'Input dan kelola nilai siswa.',
            'icon' => 'heroicon-o-clipboard-document-check',
            'url' => $jadwalInput === null
                ? null
                : InputNilaiPerMapel::getUrl(['jadwalMengajar' => $jadwalInput]),
            'tone' => 'primary',
        ];

        if (CetakRaporSiswa::canAccess()) {
            $actions[] = [
                'title' => 'Cetak Rapor Siswa',
                'description' => 'Cetak rapor dalam format PDF.',
                'icon' => 'heroicon-o-printer',
                'url' => CetakRaporSiswa::getUrl(),
                'tone' => 'success',
            ];
        }

        if (NilaiResource::canCreate()) {
            $actions[] = [
                'title' => 'Tambah Nilai',
                'description' => 'Tambah nilai individual siswa.',
                'icon' => 'heroicon-o-plus-circle',
                'url' => NilaiResource::getUrl('create'),
                'tone' => 'primary',
            ];
        }

        if (SiswaResource::canViewAny()) {
            $actions[] = [
                'title' => 'Kelola Siswa',
                'description' => 'Lengkapi data siswa dan akun portal.',
                'icon' => 'heroicon-o-academic-cap',
                'url' => SiswaResource::getUrl('index'),
                'tone' => 'info',
            ];
        }

        return $actions;
    }

    private function firstInputSchedule(): ?JadwalMengajar
    {
        if (! NilaiResource::canViewAny()) {
            return null;
        }

        return InputNilaiPerMapel::navigasiMataPelajaran()->first();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function progressRows(?TahunAjaran $tahunAjaran): array
    {
        $user = auth()->user();
        $guruId = $user?->guru?->getKey();

        // Class-wide progress is staff information: a student must never see
        // other classes, their homeroom teachers, or their grading progress.
        if ($user === null || $user->isSiswa()) {
            return [];
        }

        $kelasQuery = Kelas::query()
            ->with(['waliKelas.user'])
            ->withCount('siswaAktif')
            ->orderBy('tingkat')
            ->orderBy('nama_kelas')
            ->limit(6);

        if ($user?->isGuru() && $guruId !== null) {
            $kelasQuery->where(function (Builder $query) use ($guruId): void {
                $query
                    ->where('wali_kelas_id', $guruId)
                    ->orWhereHas(
                        'jadwalMengajars',
                        static fn (Builder $jadwalQuery): Builder => $jadwalQuery
                            ->where('guru_id', $guruId),
                    );
            });
        }

        return $kelasQuery
            ->get()
            ->map(function (Kelas $kelas) use ($tahunAjaran, $user, $guruId): array {
                $jadwalQuery = JadwalMengajar::query()
                    ->where('kelas_id', $kelas->getKey());

                if ($tahunAjaran !== null) {
                    $jadwalQuery->where('tahun_ajaran_id', $tahunAjaran->getKey());
                }

                if ($user?->isGuru() && $guruId !== null) {
                    $jadwalQuery->where('guru_id', $guruId);
                }

                $jadwalIds = $jadwalQuery->pluck('id');
                $jadwalCount = $jadwalIds->count();
                $studentCount = (int) $kelas->siswa_aktif_count;
                $expectedCount = $studentCount * $jadwalCount;

                $finalCount = $expectedCount > 0
                    ? Nilai::query()
                        ->whereIn('jadwal_mengajar_id', $jadwalIds)
                        ->where('is_submitted', true)
                        ->count()
                    : 0;

                $percent = $expectedCount > 0
                    ? min(100, (int) round(($finalCount / $expectedCount) * 100))
                    : 0;

                return [
                    'kelas' => $kelas->nama_kelas,
                    'waliKelas' => $kelas->waliKelas?->nama ?? '-',
                    'siswa' => $studentCount,
                    'progress' => $percent,
                    'status' => $this->statusLabel($expectedCount, $percent),
                    'statusTone' => $this->statusTone($expectedCount, $percent),
                ];
            })
            ->values()
            ->all();
    }

    private function statusLabel(int $expectedCount, int $percent): string
    {
        if ($expectedCount === 0) {
            return 'Belum ada pengajar';
        }

        return $percent >= 100
            ? 'Siap dicetak'
            : 'Nilai belum lengkap';
    }

    private function statusTone(int $expectedCount, int $percent): string
    {
        if ($expectedCount === 0) {
            return 'neutral';
        }

        return $percent >= 100 ? 'success' : 'warning';
    }

    private function roleLabel(): string
    {
        return match (auth()->user()?->role) {
            'admin' => 'Administrator',
            'guru' => 'Guru',
            'siswa' => 'Siswa',
            default => 'Pengguna',
        };
    }
}

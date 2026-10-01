<?php

namespace App\Services;

use App\Models\AbsensiSiswa;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AbsensiSiswaService
{
    /**
     * @return array<int, array<string, int|string>>
     */
    public function rows(Kelas $kelas, TahunAjaran $tahunAjaran): array
    {
        $this->ensureActorCanManageClass($kelas, $tahunAjaran);

        $absensiBySiswa = AbsensiSiswa::query()
            ->where('tahun_ajaran_id', $tahunAjaran->getKey())
            ->get()
            ->keyBy('siswa_id');

        return app(KenaikanKelasService::class)
            ->siswaUntukKelasTahunQuery($kelas->getKey(), $tahunAjaran->getKey())
            ->orderBy('nama_lengkap')
            ->get()
            ->map(function (Siswa $siswa) use ($absensiBySiswa): array {
                /** @var AbsensiSiswa|null $absensi */
                $absensi = $absensiBySiswa->get($siswa->getKey());

                return [
                    'siswa_id' => $siswa->getKey(),
                    'nisn' => $siswa->nisn,
                    'nama_lengkap' => $siswa->nama_lengkap,
                    'sakit' => $absensi?->sakit ?? 0,
                    'izin' => $absensi?->izin ?? 0,
                    'alpa' => $absensi?->alpa ?? 0,
                ];
            })
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function save(Kelas $kelas, TahunAjaran $tahunAjaran, array $rows): int
    {
        $this->ensureActorCanManageClass($kelas, $tahunAjaran);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'absensi_siswa' => 'Belum ada data siswa untuk disimpan.',
            ]);
        }

        $siswaIds = collect($rows)
            ->pluck('siswa_id')
            ->filter(static fn (mixed $id): bool => filled($id))
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        $jumlahSiswaKelas = app(KenaikanKelasService::class)
            ->siswaUntukKelasTahunQuery($kelas->getKey(), $tahunAjaran->getKey())
            ->whereIn('id', $siswaIds)
            ->count();

        if ($jumlahSiswaKelas !== $siswaIds->count() || $siswaIds->count() !== count($rows)) {
            throw ValidationException::withMessages([
                'absensi_siswa' => 'Data absensi harus berisi setiap siswa kelas tepat satu kali.',
            ]);
        }

        return DB::transaction(function () use ($tahunAjaran, $rows): int {
            foreach ($rows as $index => $row) {
                AbsensiSiswa::query()->updateOrCreate(
                    [
                        'siswa_id' => (int) $row['siswa_id'],
                        'tahun_ajaran_id' => $tahunAjaran->getKey(),
                    ],
                    [
                        'sakit' => $this->normalizeJumlah($row['sakit'] ?? null, "absensi_siswa.{$index}.sakit"),
                        'izin' => $this->normalizeJumlah($row['izin'] ?? null, "absensi_siswa.{$index}.izin"),
                        'alpa' => $this->normalizeJumlah($row['alpa'] ?? null, "absensi_siswa.{$index}.alpa"),
                    ],
                );
            }

            return count($rows);
        });
    }

    public function ensureActorCanManageClass(Kelas $kelas, TahunAjaran $tahunAjaran): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            throw new AuthorizationException('Silakan masuk terlebih dahulu untuk mengelola absensi.');
        }

        if ($user->isAdmin()) {
            return;
        }

        $guru = $user->guru;

        if (
            $user->isGuru()
            && $guru?->can_input_nilai === true
            && $tahunAjaran->is_active
            && $kelas->jadwalMengajars()
                ->where('guru_id', $guru->getKey())
                ->where('tahun_ajaran_id', $tahunAjaran->getKey())
                ->exists()
        ) {
            return;
        }

        throw new AuthorizationException('Anda tidak memiliki akses untuk mengelola absensi kelas ini.');
    }

    private function normalizeJumlah(mixed $value, string $field): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (
            ! is_numeric($value)
            || (int) $value < 0
            || (int) $value > 365
            || floor((float) $value) !== (float) $value
        ) {
            throw ValidationException::withMessages([
                $field => 'Jumlah absensi harus berupa bilangan bulat antara 0 sampai 365.',
            ]);
        }

        return (int) $value;
    }
}

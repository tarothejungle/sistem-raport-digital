<?php

namespace App\Services;

use App\Models\JadwalMengajar;
use App\Models\KkmPengajar;
use App\Models\Nilai;
use App\Models\Siswa;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class NilaiBatchService
{
    public function __construct(
        private readonly NilaiService $nilaiService,
    ) {
    }

    /**
     * Menyusun seluruh siswa kelas beserta nilai yang pernah disimpan.
     *
     * @return array<int, array{
     *     siswa_id: int,
     *     nisn: string,
     *     nama_lengkap: string,
     *     nilai_angka: int|null,
     *     indeks_ketercapaian: string|null
     * }>
     */
    public function rowsForSchedule(JadwalMengajar $jadwalMengajar): array
    {
        $nilaiBySiswa = Nilai::query()
            ->where('jadwal_mengajar_id', $jadwalMengajar->getKey())
            ->get()
            ->keyBy('siswa_id');

        return Siswa::query()
            ->where('kelas_id', $jadwalMengajar->kelas_id)
            ->orderBy('nama_lengkap')
            ->get()
            ->map(function (Siswa $siswa) use ($nilaiBySiswa): array {
                /** @var Nilai|null $nilai */
                $nilai = $nilaiBySiswa->get($siswa->getKey());

                return [
                    'siswa_id' => $siswa->getKey(),
                    'nisn' => $siswa->nisn,
                    'nama_lengkap' => $siswa->nama_lengkap,
                    'nilai_angka' => $nilai?->nilai_angka,
                    'indeks_ketercapaian' => $nilai?->indeks_ketercapaian,
                ];
            })
            ->all();
    }

    public function defaultKkm(JadwalMengajar $jadwalMengajar): int
    {
        return $this->nilaiService->kkmUntukJadwal($jadwalMengajar);
    }

    /**
     * @param array<int, array{siswa_id: int|string, nilai_angka: int|string|null}> $nilaiSiswa
     */
    public function save(
        JadwalMengajar $jadwalMengajar,
        mixed $kkm,
        string $deskripsi,
        array $nilaiSiswa,
    ): int {
        $jadwalMengajar->loadMissing('mataPelajaran');

        $this->nilaiService->ensureActorCanManageSchedule($jadwalMengajar);

        $kkm = $this->normalizeKkm($kkm);
        $deskripsi = trim($deskripsi);

        if ($deskripsi === '') {
            throw ValidationException::withMessages([
                'deskripsi' => 'Deskripsi nilai wajib diisi.',
            ]);
        }

        if ($nilaiSiswa === []) {
            throw ValidationException::withMessages([
                'nilai_siswa' => 'Belum ada data siswa untuk disimpan.',
            ]);
        }

        $siswaIds = collect($nilaiSiswa)
            ->pluck('siswa_id')
            ->filter(static fn (mixed $id): bool => filled($id))
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        $jumlahSiswaKelas = Siswa::query()
            ->where('kelas_id', $jadwalMengajar->kelas_id)
            ->whereIn('id', $siswaIds)
            ->count();

        if ($jumlahSiswaKelas !== $siswaIds->count()) {
            throw ValidationException::withMessages([
                'nilai_siswa' => 'Terdapat siswa yang bukan berasal dari kelas pada menu ini.',
            ]);
        }

        return DB::transaction(function () use (
            $jadwalMengajar,
            $kkm,
            $deskripsi,
            $nilaiSiswa,
        ): int {
            KkmPengajar::query()->updateOrCreate(
                [
                    'guru_id' => $jadwalMengajar->guru_id,
                    'mapel_id' => $jadwalMengajar->mapel_id,
                    'tahun_ajaran_id' => $jadwalMengajar->tahun_ajaran_id,
                ],
                [
                    'kkm' => $kkm,
                ],
            );

            foreach ($nilaiSiswa as $index => $barisNilai) {
                $nilaiAngka = $this->normalizeNilaiAngka(
                    $barisNilai['nilai_angka'] ?? null,
                    "nilai_siswa.{$index}.nilai_angka",
                );

                $indeksKetercapaian = $this->nilaiService
                    ->determineIndeksKetercapaian($nilaiAngka);

                Nilai::query()->updateOrCreate(
                    [
                        'siswa_id' => (int) $barisNilai['siswa_id'],
                        'jadwal_mengajar_id' => $jadwalMengajar->getKey(),
                    ],
                    [
                        'kkm' => $kkm,
                        'nilai_angka' => $nilaiAngka,
                        'nilai_akhir' => $nilaiAngka,
                        'predikat' => $this->nilaiService->determinePredikat(
                            $nilaiAngka,
                            $kkm,
                        ),
                        'indeks_ketercapaian' => $indeksKetercapaian,
                        'deskripsi' => $this->nilaiService->buildDeskripsiKetercapaian(
                            $indeksKetercapaian,
                            $deskripsi,
                        ),
                        'is_submitted' => true,
                    ],
                );
            }

            return count($nilaiSiswa);
        });
    }

    private function normalizeKkm(mixed $kkm): int
    {
        if (
            ! is_numeric($kkm)
            || (int) $kkm < 0
            || (int) $kkm > 100
        ) {
            throw ValidationException::withMessages([
                'kkm' => 'KKM harus berupa angka antara 0 sampai 100.',
            ]);
        }

        return (int) $kkm;
    }

    private function normalizeNilaiAngka(
        mixed $nilaiAngka,
        string $field,
    ): int {
        if (
            ! is_numeric($nilaiAngka)
            || (int) $nilaiAngka < 0
            || (int) $nilaiAngka > 100
            || floor((float) $nilaiAngka) !== (float) $nilaiAngka
        ) {
            throw ValidationException::withMessages([
                $field => 'Nilai harus berupa bilangan bulat antara 0 sampai 100.',
            ]);
        }

        return (int) $nilaiAngka;
    }
}
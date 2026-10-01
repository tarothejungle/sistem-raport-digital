<?php

namespace App\Services;

use App\Models\JadwalMengajar;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class LegerPtsService
{
    /**
     * @var array<string, array{nama: string, label: string, kelompok: string, aliases: array<int, string>}>
     */
    public const MAPEL = [
        'QH' => ['nama' => "Al Qur'an Hadits", 'label' => 'AH', 'kelompok' => 'A', 'aliases' => ['QH', 'Al Quran Hadits', "Al Qur'an Hadits"]],
        'AA' => ['nama' => 'Aqidah Akhlak', 'label' => 'AA', 'kelompok' => 'A', 'aliases' => ['AA', 'Aqidah Akhlak']],
        'FQ' => ['nama' => 'Fiqih', 'label' => 'Fiqih', 'kelompok' => 'A', 'aliases' => ['FQ', 'Fiqih', 'Fikih']],
        'PPKn' => ['nama' => 'Pendidikan Pancasila dan Kewarganegaraan', 'label' => 'PPKN', 'kelompok' => 'A', 'aliases' => ['PPKn', 'PKn', 'Pendidikan Pancasila dan Kewarganegaraan']],
        'BIN' => ['nama' => 'Bahasa Indonesia', 'label' => 'BIn', 'kelompok' => 'A', 'aliases' => ['BIN', 'Bahasa Indonesia']],
        'BAR' => ['nama' => 'Bahasa Arab', 'label' => 'BA', 'kelompok' => 'A', 'aliases' => ['BAR', 'Bahasa Arab']],
        'SBK' => ['nama' => 'Seni Budaya', 'label' => 'SB', 'kelompok' => 'B', 'aliases' => ['SBK', 'Seni Budaya']],
        'MTK' => ['nama' => 'Matematika', 'label' => 'MTK', 'kelompok' => 'A', 'aliases' => ['MTK', 'MAT', 'Matematika']],
        'PJOK' => ['nama' => 'Pendidikan Jasmani, Olahraga, dan Kesehatan', 'label' => 'PJOK', 'kelompok' => 'B', 'aliases' => ['PJOK', 'OLGA', 'Pendidikan Jasmani Olahraga dan Kesehatan']],
        'TAHFIDZ' => ['nama' => "Tahfidz Al Qur'an", 'label' => 'Tahfidz', 'kelompok' => 'B', 'aliases' => ['TAHFIDZ', 'Tahfidz Al Quran', "Tahfidz Al Qur'an"]],
        'BING' => ['nama' => 'Bahasa Inggris', 'label' => 'BIng', 'kelompok' => 'B', 'aliases' => ['BING', 'Bahasa Inggris']],
    ];

    /**
     * @return array{
     *     kelas: Kelas,
     *     tahun_ajaran: TahunAjaran,
     *     mapel: array<string, array{nama: string, label: string, kelompok: string, kkm: int|null}>,
     *     rows: array<int, array<string, mixed>>,
     *     lengkap: bool
     * }
     */
    public function build(int $kelasId, int $tahunAjaranId): array
    {
        $kelas = Kelas::query()->findOrFail($kelasId);
        $tahunAjaran = TahunAjaran::query()->findOrFail($tahunAjaranId);
        $siswas = app(KenaikanKelasService::class)
            ->siswaUntukKelasTahunQuery($kelasId, $tahunAjaranId)
            ->with([
                'catatanRapors' => static fn (HasMany $query): HasMany => $query
                    ->where('tahun_ajaran_id', $tahunAjaranId),
            ])
            ->orderBy('nama_lengkap')
            ->get();

        $jadwalByKode = $this->jadwalByKode(
            $kelasId,
            $tahunAjaranId,
            $siswas->modelKeys(),
        );

        $mapel = collect(self::MAPEL)
            ->mapWithKeys(function (array $definition, string $kode) use ($jadwalByKode): array {
                $jadwal = $jadwalByKode->get($kode);

                return [$kode => [
                    'nama' => $definition['nama'],
                    'label' => $definition['label'],
                    'kelompok' => $definition['kelompok'],
                    'kkm' => $jadwal === null
                        ? null
                        : app(NilaiService::class)->kkmUntukJadwal($jadwal),
                ]];
            })
            ->all();

        $rows = $siswas
            ->values()
            ->map(function (Siswa $siswa, int $index) use ($jadwalByKode, $mapel): array {
                $catatanRapor = $siswa->catatanRapors->first();
                $mataPelajaran = collect(array_keys(self::MAPEL))
                    ->mapWithKeys(function (string $kode) use ($jadwalByKode, $mapel, $siswa): array {
                        $jadwal = $jadwalByKode->get($kode);
                        $record = $jadwal?->nilais
                            ->firstWhere('siswa_id', $siswa->getKey());

                        return [$kode => [
                            'kkm' => $record?->kkm ?? $mapel[$kode]['kkm'],
                            'nilai_angka' => $record?->nilai_angka,
                            'predikat' => $record?->predikat,
                            'deskripsi' => $record?->deskripsi,
                            'guru' => $jadwal?->guru?->nama,
                        ]];
                    })
                    ->all();
                $lengkap = collect($mataPelajaran)->every(
                    static fn (array $mapel): bool => is_int($mapel['nilai_angka'])
                        || is_float($mapel['nilai_angka']),
                );
                $jumlah = $lengkap
                    ? (int) collect($mataPelajaran)->sum('nilai_angka')
                    : null;

                return [
                    'no' => $index + 1,
                    'siswa_id' => $siswa->getKey(),
                    'nama_siswa' => $siswa->nama_lengkap,
                    'nisn' => $siswa->nisn,
                    'mata_pelajaran' => $mataPelajaran,
                    'jumlah' => $jumlah,
                    'ranking' => null,
                    'saran' => $catatanRapor?->saran,
                ];
            })
            ->all();

        $rows = $this->applyCompetitionRanking($rows);

        return [
            'kelas' => $kelas,
            'tahun_ajaran' => $tahunAjaran,
            'mapel' => $mapel,
            'rows' => $rows,
            'lengkap' => $jadwalByKode->count() === count(self::MAPEL),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function applyCompetitionRanking(array $rows): array
    {
        $ranked = collect($rows)
            ->filter(static fn (array $row): bool => $row['jumlah'] !== null)
            ->sortByDesc('jumlah')
            ->values();
        $rankBySiswa = [];
        $previousTotal = null;
        $previousRank = null;

        foreach ($ranked as $index => $row) {
            $rank = $previousTotal === $row['jumlah']
                ? $previousRank
                : $index + 1;
            $rankBySiswa[$row['siswa_id']] = $rank;
            $previousTotal = $row['jumlah'];
            $previousRank = $rank;
        }

        return collect($rows)
            ->map(function (array $row) use ($rankBySiswa): array {
                $row['ranking'] = $rankBySiswa[$row['siswa_id']] ?? null;

                return $row;
            })
            ->all();
    }

    /**
     * @param  array<int, int|string>  $siswaIds
     * @return Collection<string, JadwalMengajar>
     */
    private function jadwalByKode(
        int $kelasId,
        int $tahunAjaranId,
        array $siswaIds,
    ): Collection {
        return JadwalMengajar::query()
            ->with([
                'mataPelajaran',
                'guru.user',
                'nilais' => static fn (HasMany $query): HasMany => $query
                    ->whereIn('siswa_id', $siswaIds)
                    ->where('is_submitted', true),
            ])
            ->where('kelas_id', $kelasId)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->get()
            ->mapWithKeys(function (JadwalMengajar $jadwal): array {
                $kode = $this->canonicalKode(
                    $jadwal->mataPelajaran?->kode_mapel,
                    $jadwal->mataPelajaran?->nama_mapel,
                );

                return $kode === null ? [] : [$kode => $jadwal];
            });
    }

    private function canonicalKode(?string $kode, ?string $nama): ?string
    {
        $candidates = collect([$kode, $nama])
            ->filter()
            ->map(fn (string $value): string => $this->normalize($value));

        foreach (self::MAPEL as $canonical => $definition) {
            $aliases = collect($definition['aliases'])
                ->push($canonical)
                ->map(fn (string $alias): string => $this->normalize($alias));

            if ($candidates->intersect($aliases)->isNotEmpty()) {
                return $canonical;
            }
        }

        return null;
    }

    private function normalize(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->toString();
    }
}

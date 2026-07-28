<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\RiwayatKelasSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class KenaikanKelasService
{
    private const TINGKAT_LULUS = 6;

    /**
     * @param  iterable<int, Siswa|int|string>  $records
     * @return array{promoted: int, target_year: string}
     */
    public function promote(iterable $records, int $tahunAjaranAsalId, int $tahunAjaranTujuanId): array
    {
        $this->validateTahunAjaran($tahunAjaranAsalId, $tahunAjaranTujuanId);

        $siswas = $this->selectedSiswas($records, $tahunAjaranAsalId);
        $errors = [];
        $plan = [];

        foreach ($siswas as $siswa) {
            if ($siswa->isAlumni()) {
                $errors[] = "{$siswa->nama_lengkap} sudah berstatus alumni.";

                continue;
            }

            $kelasAsal = $this->kelasUntukTahunAjaran($siswa, $tahunAjaranAsalId);

            if ($kelasAsal === null) {
                $errors[] = "{$siswa->nama_lengkap} belum memiliki kelas asal.";

                continue;
            }

            if ($kelasAsal->tingkat >= self::TINGKAT_LULUS) {
                $errors[] = "{$siswa->nama_lengkap} berada di {$kelasAsal->nama_kelas}; gunakan aksi Luluskan Terpilih.";

                continue;
            }

            $kelasTujuan = $this->targetKelasUntuk($kelasAsal);

            if ($kelasTujuan === null) {
                $errors[] = "Target untuk {$kelasAsal->nama_kelas} belum ditemukan. Buat kelas tingkat ".($kelasAsal->tingkat + 1).' dengan rombel yang sesuai.';

                continue;
            }

            if ($this->sudahAdaDiTahunTujuan($siswa, $tahunAjaranTujuanId)) {
                $errors[] = "{$siswa->nama_lengkap} sudah memiliki riwayat pada tahun ajaran tujuan.";

                continue;
            }

            $plan[] = [$siswa, $kelasAsal, $kelasTujuan];
        }

        $this->throwIfErrors($errors);

        $targetYear = TahunAjaran::query()->findOrFail($tahunAjaranTujuanId);

        DB::transaction(function () use ($plan, $tahunAjaranAsalId, $tahunAjaranTujuanId): void {
            foreach ($plan as [$siswa, $kelasAsal, $kelasTujuan]) {
                $this->catatRiwayat(
                    $siswa,
                    $tahunAjaranAsalId,
                    $kelasAsal,
                    RiwayatKelasSiswa::STATUS_AKTIF,
                );

                RiwayatKelasSiswa::query()->create([
                    'siswa_id' => $siswa->getKey(),
                    'tahun_ajaran_id' => $tahunAjaranTujuanId,
                    'kelas_id' => $kelasTujuan->getKey(),
                    'status' => RiwayatKelasSiswa::STATUS_AKTIF,
                    'diproses_pada' => now(),
                ]);

                $siswa->update([
                    'kelas_id' => $kelasTujuan->getKey(),
                    'status' => Siswa::STATUS_AKTIF,
                    'tahun_lulus_id' => null,
                    'tanggal_lulus' => null,
                    'keterangan_alumni' => null,
                ]);
            }
        });

        return [
            'promoted' => count($plan),
            'target_year' => $targetYear->label,
        ];
    }

    /**
     * @param  iterable<int, Siswa|int|string>  $records
     * @param  array<string, mixed>  $data
     * @return array{graduated: int, source_year: string}
     */
    public function graduate(iterable $records, int $tahunAjaranAsalId, array $data = []): array
    {
        $siswas = $this->selectedSiswas($records, $tahunAjaranAsalId);
        $tahunAjaranAsal = TahunAjaran::query()->findOrFail($tahunAjaranAsalId);
        $errors = [];
        $plan = [];

        foreach ($siswas as $siswa) {
            if ($siswa->isAlumni()) {
                $errors[] = "{$siswa->nama_lengkap} sudah berstatus alumni.";

                continue;
            }

            $kelasAsal = $this->kelasUntukTahunAjaran($siswa, $tahunAjaranAsalId);

            if ($kelasAsal === null) {
                $errors[] = "{$siswa->nama_lengkap} belum memiliki kelas asal.";

                continue;
            }

            if ($kelasAsal->tingkat < self::TINGKAT_LULUS) {
                $errors[] = "{$siswa->nama_lengkap} masih di {$kelasAsal->nama_kelas}; gunakan aksi Naikkan Terpilih.";

                continue;
            }

            $plan[] = [$siswa, $kelasAsal];
        }

        $this->throwIfErrors($errors);

        DB::transaction(function () use ($plan, $tahunAjaranAsalId, $data): void {
            foreach ($plan as [$siswa, $kelasAsal]) {
                $this->catatRiwayat(
                    $siswa,
                    $tahunAjaranAsalId,
                    $kelasAsal,
                    RiwayatKelasSiswa::STATUS_LULUS,
                    true,
                );

                $siswa->update([
                    'status' => Siswa::STATUS_ALUMNI,
                    'tahun_lulus_id' => $tahunAjaranAsalId,
                    'tanggal_lulus' => $data['tanggal_lulus'] ?? now()->toDateString(),
                    'keterangan_alumni' => $data['keterangan_alumni'] ?? null,
                ]);
            }
        });

        return [
            'graduated' => count($plan),
            'source_year' => $tahunAjaranAsal->label,
        ];
    }

    public function kelasUntukTahunAjaran(Siswa $siswa, ?int $tahunAjaranId): ?Kelas
    {
        if ($tahunAjaranId !== null && $siswa->relationLoaded('riwayatKelasSiswas')) {
            $riwayat = $siswa->riwayatKelasSiswas
                ->firstWhere('tahun_ajaran_id', $tahunAjaranId);

            if ($riwayat?->kelas !== null) {
                return $riwayat->kelas;
            }
        }

        if ($tahunAjaranId !== null) {
            $riwayat = RiwayatKelasSiswa::query()
                ->with('kelas')
                ->where('siswa_id', $siswa->getKey())
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->first();

            if ($riwayat?->kelas !== null) {
                return $riwayat->kelas;
            }
        }

        return $siswa->kelas;
    }

    public function targetKelasUntuk(Kelas $kelasAsal): ?Kelas
    {
        if ($kelasAsal->tingkat >= self::TINGKAT_LULUS) {
            return null;
        }

        $targetTingkat = $kelasAsal->tingkat + 1;
        $targetKelas = Kelas::query()
            ->where('tingkat', $targetTingkat)
            ->orderBy('nama_kelas')
            ->get();

        if ($targetKelas->isEmpty()) {
            return null;
        }

        $suffixAsal = $this->suffixRombel($kelasAsal);
        $match = $targetKelas->first(
            fn (Kelas $kelas): bool => $this->suffixRombel($kelas) === $suffixAsal,
        );

        return $match ?? ($targetKelas->count() === 1 ? $targetKelas->first() : null);
    }

    public function sudahAdaRiwayatTahun(int $tahunAjaranId): bool
    {
        return RiwayatKelasSiswa::query()
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->exists();
    }

    public function siswaUntukKelasTahunQuery(int $kelasId, ?int $tahunAjaranId): Builder
    {
        if ($tahunAjaranId === null) {
            return Siswa::query()
                ->aktif()
                ->where('kelas_id', $kelasId);
        }

        $riwayatKelasTahunAda = RiwayatKelasSiswa::query()
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('kelas_id', $kelasId)
            ->exists();

        if (! $riwayatKelasTahunAda) {
            return Siswa::query()
                ->aktif()
                ->where('kelas_id', $kelasId);
        }

        $tahunAjaranAktif = TahunAjaran::query()
            ->whereKey($tahunAjaranId)
            ->where('is_active', true)
            ->exists();

        return Siswa::query()
            ->where(function (Builder $query) use ($kelasId, $tahunAjaranId, $tahunAjaranAktif): void {
                $query->whereHas(
                    'riwayatKelasSiswas',
                    static fn (Builder $riwayatQuery): Builder => $riwayatQuery
                        ->where('tahun_ajaran_id', $tahunAjaranId)
                        ->where('kelas_id', $kelasId),
                );

                if (! $tahunAjaranAktif) {
                    return;
                }

                $query->orWhere(function (Builder $fallbackQuery) use ($kelasId, $tahunAjaranId): void {
                    $fallbackQuery
                        ->aktif()
                        ->where('kelas_id', $kelasId)
                        ->whereDoesntHave(
                            'riwayatKelasSiswas',
                            static fn (Builder $riwayatQuery): Builder => $riwayatQuery
                                ->where('tahun_ajaran_id', $tahunAjaranId),
                        );
                });
            });
    }

    /**
     * @param  iterable<int, Siswa|int|string>  $records
     * @return EloquentCollection<int, Siswa>
     */
    private function selectedSiswas(iterable $records, int $tahunAjaranAsalId): EloquentCollection
    {
        $ids = Collection::make($records)
            ->map(static fn (Siswa|int|string $record): int => $record instanceof Siswa
                ? (int) $record->getKey()
                : (int) $record)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            throw ValidationException::withMessages([
                'siswa' => 'Pilih minimal satu siswa terlebih dahulu.',
            ]);
        }

        return Siswa::query()
            ->with([
                'kelas',
                'riwayatKelasSiswas' => static fn ($query) => $query
                    ->where('tahun_ajaran_id', $tahunAjaranAsalId)
                    ->with('kelas'),
            ])
            ->whereKey($ids->all())
            ->get();
    }

    private function validateTahunAjaran(int $tahunAjaranAsalId, int $tahunAjaranTujuanId): void
    {
        if ($tahunAjaranAsalId === $tahunAjaranTujuanId) {
            throw ValidationException::withMessages([
                'tahun_ajaran' => 'Tahun ajaran asal dan tujuan tidak boleh sama.',
            ]);
        }

        TahunAjaran::query()->findOrFail($tahunAjaranAsalId);
        TahunAjaran::query()->findOrFail($tahunAjaranTujuanId);
    }

    private function sudahAdaDiTahunTujuan(Siswa $siswa, int $tahunAjaranTujuanId): bool
    {
        return RiwayatKelasSiswa::query()
            ->where('siswa_id', $siswa->getKey())
            ->where('tahun_ajaran_id', $tahunAjaranTujuanId)
            ->exists();
    }

    private function catatRiwayat(
        Siswa $siswa,
        int $tahunAjaranId,
        Kelas $kelas,
        string $status,
        bool $markProcessed = false,
    ): void {
        RiwayatKelasSiswa::query()->updateOrCreate(
            [
                'siswa_id' => $siswa->getKey(),
                'tahun_ajaran_id' => $tahunAjaranId,
            ],
            [
                'kelas_id' => $kelas->getKey(),
                'status' => $status,
                'diproses_pada' => $markProcessed ? now() : null,
            ],
        );
    }

    /**
     * @param  array<int, string>  $errors
     */
    private function throwIfErrors(array $errors): void
    {
        if ($errors === []) {
            return;
        }

        throw ValidationException::withMessages([
            'kenaikan_kelas' => array_slice($errors, 0, 8),
        ]);
    }

    private function suffixRombel(Kelas $kelas): string
    {
        $name = strtolower($kelas->nama_kelas);
        $normalized = preg_replace('/[^a-z0-9]+/', '', $name) ?? '';
        $normalized = preg_replace('/^kelas/', '', $normalized) ?? $normalized;

        return preg_replace('/^'.$kelas->tingkat.'/', '', $normalized) ?? $normalized;
    }
}

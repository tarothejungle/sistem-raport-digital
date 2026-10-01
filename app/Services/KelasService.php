<?php

namespace App\Services;

use App\Models\Kelas;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class KelasService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Kelas
    {
        $attributes = $this->attributes($data);
        $this->ensureNamaKelasAvailable($attributes['nama_kelas']);

        return DB::transaction(
            static fn (): Kelas => Kelas::query()->create($attributes),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, Kelas>
     */
    public function createMany(array $rows): array
    {
        if ($rows === []) {
            throw ValidationException::withMessages([
                'kelas' => 'Tambahkan minimal satu kelas.',
            ]);
        }

        return DB::transaction(function () use ($rows): array {
            $created = [];
            $seenNames = [];

            foreach ($rows as $index => $row) {
                $attributes = $this->attributes($row);
                $normalizedName = strtolower($attributes['nama_kelas']);

                if (isset($seenNames[$normalizedName])) {
                    throw ValidationException::withMessages([
                        "kelas.{$index}.nama_kelas" => 'Nama kelas muncul lebih dari satu kali.',
                    ]);
                }

                $seenNames[$normalizedName] = true;
                $this->ensureNamaKelasAvailable($attributes['nama_kelas']);
                $created[] = Kelas::query()->create($attributes);
            }

            return $created;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Kelas $kelas, array $data): Kelas
    {
        $attributes = $this->attributes($data);
        $this->ensureNamaKelasAvailable($attributes['nama_kelas'], $kelas);

        return DB::transaction(function () use ($kelas, $attributes): Kelas {
            $kelas->update($attributes);

            return $kelas->refresh();
        });
    }

    public function delete(Kelas $kelas): bool
    {
        if (
            $kelas->siswas()->exists()
            || $kelas->jadwalMengajars()->exists()
            || $kelas->riwayatKelasSiswas()->exists()
        ) {
            throw ValidationException::withMessages([
                'kelas' => 'Kelas tidak dapat dihapus karena masih memiliki data siswa, jadwal mengajar, atau riwayat kelas.',
            ]);
        }

        return DB::transaction(
            static fn (): bool => (bool) $kelas->delete(),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{nama_kelas: string, tingkat: int, wali_kelas_id: int|null}
     */
    private function attributes(array $data): array
    {
        $namaKelas = trim((string) ($data['nama_kelas'] ?? ''));
        $tingkat = filter_var($data['tingkat'] ?? null, FILTER_VALIDATE_INT);
        $waliKelasId = $data['wali_kelas_id'] ?? null;

        if ($namaKelas === '') {
            throw ValidationException::withMessages([
                'nama_kelas' => 'Nama kelas wajib diisi.',
            ]);
        }

        if ($tingkat === false || $tingkat < 1 || $tingkat > 12) {
            throw ValidationException::withMessages([
                'tingkat' => 'Tingkat kelas harus berupa angka 1 sampai 12.',
            ]);
        }

        return [
            'nama_kelas' => $namaKelas,
            'tingkat' => $tingkat,
            'wali_kelas_id' => filled($waliKelasId) ? (int) $waliKelasId : null,
        ];
    }

    private function ensureNamaKelasAvailable(string $namaKelas, ?Kelas $ignore = null): void
    {
        $query = Kelas::query()->where('nama_kelas', $namaKelas);

        if ($ignore !== null) {
            $query->whereKeyNot($ignore->getKey());
        }

        if (! $query->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'nama_kelas' => 'Nama kelas tersebut sudah digunakan.',
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\JadwalMengajar;
use App\Models\KkmPengajar;
use App\Models\Nilai;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

final class NilaiService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function prepareForPersistence(
        array $data,
        ?Nilai $ignorable = null,
    ): array {
        $jadwalMengajar = JadwalMengajar::query()
            ->with('mataPelajaran')
            ->find($data['jadwal_mengajar_id'] ?? null);

        if ($jadwalMengajar === null) {
            throw ValidationException::withMessages([
                'jadwal_mengajar_id' => 'Jadwal mengajar tidak ditemukan.',
            ]);
        }

        $this->ensureActorCanManageSchedule($jadwalMengajar);

        $siswaSesuaiKelas = app(KenaikanKelasService::class)
            ->siswaUntukKelasTahunQuery(
                (int) $jadwalMengajar->kelas_id,
                (int) $jadwalMengajar->tahun_ajaran_id,
            )
            ->whereKey($data['siswa_id'] ?? null)
            ->exists();

        if (! $siswaSesuaiKelas) {
            throw ValidationException::withMessages([
                'siswa_id' => 'Siswa harus berasal dari kelas pada jadwal mengajar yang dipilih.',
            ]);
        }

        $nilaiSudahAda = Nilai::query()
            ->where('siswa_id', $data['siswa_id'])
            ->where('jadwal_mengajar_id', $jadwalMengajar->getKey())
            ->when(
                $ignorable !== null,
                fn ($query) => $query->whereKeyNot($ignorable->getKey()),
            )
            ->exists();

        if ($nilaiSudahAda) {
            throw ValidationException::withMessages([
                'siswa_id' => 'Nilai siswa untuk jadwal mengajar ini sudah ada.',
            ]);
        }

        $nilaiAngka = $this->normalizeScore(
            $data['nilai_angka'] ?? null,
            'nilai_angka',
        );

        if ($nilaiAngka === null) {
            throw ValidationException::withMessages([
                'nilai_angka' => 'Nilai angka wajib diisi.',
            ]);
        }

        $kkm = $this->kkmUntukJadwal($jadwalMengajar);

        $data['nilai_angka'] = $nilaiAngka;
        $data['nilai_akhir'] = $nilaiAngka;
        $data['kkm'] = $kkm;
        $data['predikat'] = $this->determinePredikat(
            $nilaiAngka,
            $kkm,
        );
        $data['indeks_ketercapaian'] = $this->determineIndeksKetercapaian(
            $nilaiAngka,
        );
        $data['deskripsi'] = filled($data['deskripsi'] ?? null)
            ? trim((string) $data['deskripsi'])
            : null;
        $data['is_submitted'] = (bool) ($data['is_submitted'] ?? true);

        return $data;
    }

    public function kkmUntukJadwal(
        JadwalMengajar $jadwalMengajar,
    ): int {
        $kkm = KkmPengajar::query()
            ->where('guru_id', $jadwalMengajar->guru_id)
            ->where('mapel_id', $jadwalMengajar->mapel_id)
            ->where('tahun_ajaran_id', $jadwalMengajar->tahun_ajaran_id)
            ->value('kkm');

        /*
        * Nilai default hanya dipakai apabila guru belum pernah menetapkan
        * KKM untuk mata pelajaran dan tahun ajaran tersebut.
        */
        $kkm ??= 70;

        if (! is_numeric($kkm) || $kkm < 0 || $kkm > 100) {
            throw ValidationException::withMessages([
                'kkm' => 'KKM harus berada pada rentang 0 sampai 100.',
            ]);
        }

        return (int) $kkm;
    }

    public function determinePredikat(
        int $nilaiAngka,
        int $kkm,
    ): string {
        return match (true) {
            $nilaiAngka >= 90 => 'A',
            $nilaiAngka >= 80 => 'B',
            $nilaiAngka >= $kkm => 'C',
            default => 'D',
        };
    }

    public function determineIndeksKetercapaian(
        int $nilaiAngka,
    ): string {
        return match (true) {
            $nilaiAngka >= 90 => 'Sangat Baik',
            $nilaiAngka >= 80 => 'Baik',
            $nilaiAngka >= 70 => 'Cukup',
            default => 'Kurang',
        };
    }

    public function buildDeskripsiKetercapaian(
        string $indeksKetercapaian,
        string $deskripsiLanjutan,
    ): string {
        return sprintf(
            'Siswa memiliki keterampilan yang %s dalam %s',
            strtoupper($indeksKetercapaian),
            trim($deskripsiLanjutan),
        );
    }

    public function ensureActorCanManageSchedule(
        JadwalMengajar $jadwalMengajar,
    ): void {
        $user = auth()->user();

        if (! $user instanceof User) {
            throw new AuthorizationException(
                'Silakan masuk terlebih dahulu untuk mengelola nilai.',
            );
        }

        if ($user->isAdmin()) {
            return;
        }

        $guru = $user->guru;

        if (
            $user->isGuru()
            && $guru !== null
            && $guru->can_input_nilai
            && (int) $jadwalMengajar->guru_id === (int) $guru->getKey()
            && $jadwalMengajar->tahunAjaran()
                ->where('is_active', true)
                ->exists()
        ) {
            return;
        }

        throw new AuthorizationException(
            'Anda tidak memiliki akses untuk mengelola nilai pada jadwal mengajar ini.',
        );
    }

    private function normalizeScore(
        mixed $value,
        string $field,
    ): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        if (
            ! is_numeric($value)
            || $value < 0
            || $value > 100
            || floor((float) $value) !== (float) $value
        ) {
            throw ValidationException::withMessages([
                $field => 'Nilai harus berupa bilangan bulat antara 0 sampai 100.',
            ]);
        }

        return (int) $value;
    }
}

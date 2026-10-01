<?php

namespace App\Services;

use App\Models\AbsensiSiswa;
use App\Models\CatatanRapor;
use App\Models\JadwalMengajar;
use App\Models\PengaturanMadrasah;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class RaportPdfService
{
    /**
     * @return array<string, mixed>
     */
    public function build(Siswa $siswa, TahunAjaran $tahunAjaran): array
    {
        $siswa->loadMissing([
            'kelas.waliKelas.user',
        ]);

        $kelas = app(KenaikanKelasService::class)->kelasUntukTahunAjaran(
            $siswa,
            $tahunAjaran->getKey(),
        ) ?? $siswa->kelas;

        $kelas?->loadMissing('waliKelas.user');

        $jadwalMengajars = JadwalMengajar::query()
            ->with([
                'mataPelajaran',
                'guru.user',
                'nilais' => static fn (HasMany $query): HasMany => $query
                    ->where('siswa_id', $siswa->getKey())
                    ->where('is_submitted', true),
            ])
            ->where('kelas_id', $kelas?->getKey() ?? 0)
            ->where('tahun_ajaran_id', $tahunAjaran->getKey())
            ->get()
            ->sortBy(static function (JadwalMengajar $jadwalMengajar): string {
                return sprintf(
                    '%s|%s',
                    $jadwalMengajar->mataPelajaran?->kelompok ?? 'Z',
                    strtolower($jadwalMengajar->mataPelajaran?->nama_mapel ?? ''),
                );
            })
            ->values();

        $barisNilai = $jadwalMengajars
            ->map(static function (JadwalMengajar $jadwalMengajar): array {
                $nilai = $jadwalMengajar->nilais->first();
                $mapel = $jadwalMengajar->mataPelajaran;

                return [
                    'kelompok' => $mapel?->kelompok ?? 'A',
                    'mapel' => $mapel?->nama_mapel ?? '-',
                    'guru' => $jadwalMengajar->guru?->nama ?? '-',
                    'kkm' => $nilai?->kkm
                        ?? app(NilaiService::class)->kkmUntukJadwal($jadwalMengajar),
                    'nilai_angka' => $nilai?->nilai_angka,
                    'predikat' => $nilai?->predikat,
                    'deskripsi' => $nilai?->deskripsi,
                ];
            });

        $pengaturan = PengaturanMadrasah::query()->first()
            ?? new PengaturanMadrasah([
                'nama_madrasah' => 'Sistem Rapor Digital',
            ]);
        $absensi = AbsensiSiswa::query()
            ->where('siswa_id', $siswa->getKey())
            ->where('tahun_ajaran_id', $tahunAjaran->getKey())
            ->first();

        return [
            'siswa' => $siswa,
            'kelas' => $kelas,
            'tahunAjaran' => $tahunAjaran,
            'pengaturan' => $pengaturan,
            'kelompokA' => $barisNilai
                ->where('kelompok', 'A')
                ->values(),
            'kelompokB' => $barisNilai
                ->where('kelompok', 'B')
                ->values(),
            'saran' => CatatanRapor::query()
                ->where('siswa_id', $siswa->getKey())
                ->where('tahun_ajaran_id', $tahunAjaran->getKey())
                ->value('saran'),
            'absensi' => [
                'sakit' => $absensi?->sakit ?? 0,
                'izin' => $absensi?->izin ?? 0,
                'alpa' => $absensi?->alpa ?? 0,
            ],
            'logoDataUri' => $this->imageDataUri($pengaturan->logo_path, 'public'),
            'ttdKepalaDataUri' => $this->imageDataUri($pengaturan->ttd_kepala_path, 'local'),
            'tanggalCetak' => now()
                ->locale('id')
                ->translatedFormat('d F Y'),
        ];
    }

    private function imageDataUri(?string $path, string $disk): ?string
    {
        if (blank($path)) {
            return null;
        }

        $filePath = Storage::disk($disk)->path($path);

        if (! is_file($filePath)) {
            return null;
        }

        $mimeType = mime_content_type($filePath) ?: 'image/png';

        return sprintf(
            'data:%s;base64,%s',
            $mimeType,
            base64_encode((string) file_get_contents($filePath)),
        );
    }
}

<?php

namespace App\Services;

use App\Models\CatatanRapor;
use App\Models\JadwalMengajar;
use App\Models\PengaturanMadrasah;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            'logoDataUri' => $this->imageDataUri($pengaturan->logo_path),
            'ttdKepalaDataUri' => $this->imageDataUri($pengaturan->ttd_kepala_path),
            'tanggalCetak' => now()
                ->locale('id')
                ->translatedFormat('d F Y'),
        ];
    }

    private function imageDataUri(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $filePath = storage_path('app/public/'.ltrim($path, '/'));

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

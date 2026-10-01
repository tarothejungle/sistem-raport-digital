<?php

namespace Tests\Feature;

use App\Models\AbsensiSiswa;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\RaportPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RaportPdfAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_defaults_attendance_to_zero_when_it_has_not_been_filled(): void
    {
        $kelas = Kelas::query()->create([
            'nama_kelas' => '5A',
            'tingkat' => 5,
        ]);
        $siswa = Siswa::query()->create([
            'nisn' => '1234567890',
            'nama_lengkap' => 'Budi Santoso',
            'kelas_id' => $kelas->getKey(),
            'status' => Siswa::STATUS_AKTIF,
        ]);
        $tahunAjaran = TahunAjaran::query()->create([
            'nama' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);

        $data = app(RaportPdfService::class)->build($siswa, $tahunAjaran);

        $this->assertSame([
            'sakit' => 0,
            'izin' => 0,
            'alpa' => 0,
        ], $data['absensi']);
    }

    public function test_report_format_contains_attendance_for_the_selected_school_year(): void
    {
        $kelas = Kelas::query()->create([
            'nama_kelas' => '5A',
            'tingkat' => 5,
        ]);
        $siswa = Siswa::query()->create([
            'nisn' => '1234567890',
            'nama_lengkap' => 'Budi Santoso',
            'kelas_id' => $kelas->getKey(),
            'status' => Siswa::STATUS_AKTIF,
        ]);
        $tahunDipilih = TahunAjaran::query()->create([
            'nama' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);
        $tahunLain = TahunAjaran::query()->create([
            'nama' => '2025/2026',
            'semester' => 'Genap',
            'is_active' => false,
        ]);

        AbsensiSiswa::query()->create([
            'siswa_id' => $siswa->getKey(),
            'tahun_ajaran_id' => $tahunDipilih->getKey(),
            'sakit' => 2,
            'izin' => 3,
            'alpa' => 4,
        ]);
        AbsensiSiswa::query()->create([
            'siswa_id' => $siswa->getKey(),
            'tahun_ajaran_id' => $tahunLain->getKey(),
            'sakit' => 20,
            'izin' => 30,
            'alpa' => 40,
        ]);

        $data = app(RaportPdfService::class)->build($siswa, $tahunDipilih);

        $this->assertSame([
            'sakit' => 2,
            'izin' => 3,
            'alpa' => 4,
        ], $data['absensi']);

        $html = view('pdf.CetakRapor', $data)->render();

        $this->assertStringContainsString('Ketidakhadiran', $html);
        $this->assertMatchesRegularExpression('/Sakit\s*<\/td>\s*<td[^>]*>\s*2\s*hari/s', $html);
        $this->assertMatchesRegularExpression('/Izin\s*<\/td>\s*<td[^>]*>\s*3\s*hari/s', $html);
        $this->assertMatchesRegularExpression('/Alpa\s*<\/td>\s*<td[^>]*>\s*4\s*hari/s', $html);
        $this->assertStringNotContainsString('20 hari', $html);
        $this->assertStringNotContainsString('30 hari', $html);
        $this->assertStringNotContainsString('40 hari', $html);
    }
}

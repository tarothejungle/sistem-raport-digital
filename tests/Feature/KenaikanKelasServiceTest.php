<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\RiwayatKelasSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\KenaikanKelasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KenaikanKelasServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_promotes_students_and_keeps_class_history(): void
    {
        [$sourceYear, $targetYear] = $this->createAcademicYears();
        $kelasSatu = Kelas::query()->create([
            'nama_kelas' => 'Kelas 1A',
            'tingkat' => 1,
        ]);
        $kelasDua = Kelas::query()->create([
            'nama_kelas' => 'Kelas 2A',
            'tingkat' => 2,
        ]);
        $siswa = Siswa::query()->create([
            'nisn' => '100000001',
            'nama_lengkap' => 'Siswa Naik',
            'kelas_id' => $kelasSatu->getKey(),
        ]);

        $summary = app(KenaikanKelasService::class)->promote(
            [$siswa],
            $sourceYear->getKey(),
            $targetYear->getKey(),
        );

        $this->assertSame(1, $summary['promoted']);
        $this->assertDatabaseHas('siswa', [
            'id' => $siswa->getKey(),
            'kelas_id' => $kelasDua->getKey(),
            'status' => Siswa::STATUS_AKTIF,
        ]);
        $this->assertDatabaseHas('riwayat_kelas_siswa', [
            'siswa_id' => $siswa->getKey(),
            'tahun_ajaran_id' => $sourceYear->getKey(),
            'kelas_id' => $kelasSatu->getKey(),
            'status' => RiwayatKelasSiswa::STATUS_AKTIF,
        ]);
        $this->assertDatabaseHas('riwayat_kelas_siswa', [
            'siswa_id' => $siswa->getKey(),
            'tahun_ajaran_id' => $targetYear->getKey(),
            'kelas_id' => $kelasDua->getKey(),
            'status' => RiwayatKelasSiswa::STATUS_AKTIF,
        ]);

        $this->assertTrue(
            app(KenaikanKelasService::class)
                ->siswaUntukKelasTahunQuery($kelasSatu->getKey(), $sourceYear->getKey())
                ->whereKey($siswa->getKey())
                ->exists(),
        );
    }

    public function test_it_graduates_grade_six_students_to_alumni(): void
    {
        [$sourceYear] = $this->createAcademicYears();
        $kelasEnam = Kelas::query()->create([
            'nama_kelas' => 'Kelas 6A',
            'tingkat' => 6,
        ]);
        $siswa = Siswa::query()->create([
            'nisn' => '100000002',
            'nama_lengkap' => 'Siswa Lulus',
            'kelas_id' => $kelasEnam->getKey(),
        ]);

        $summary = app(KenaikanKelasService::class)->graduate(
            [$siswa],
            $sourceYear->getKey(),
            ['tanggal_lulus' => '2026-06-30'],
        );

        $this->assertSame(1, $summary['graduated']);
        $this->assertDatabaseHas('siswa', [
            'id' => $siswa->getKey(),
            'kelas_id' => $kelasEnam->getKey(),
            'status' => Siswa::STATUS_ALUMNI,
            'tahun_lulus_id' => $sourceYear->getKey(),
            'tanggal_lulus' => '2026-06-30 00:00:00',
        ]);
        $this->assertDatabaseHas('riwayat_kelas_siswa', [
            'siswa_id' => $siswa->getKey(),
            'tahun_ajaran_id' => $sourceYear->getKey(),
            'kelas_id' => $kelasEnam->getKey(),
            'status' => RiwayatKelasSiswa::STATUS_LULUS,
        ]);
        $this->assertFalse(Siswa::query()->aktif()->whereKey($siswa->getKey())->exists());
        $this->assertTrue(
            app(KenaikanKelasService::class)
                ->siswaUntukKelasTahunQuery($kelasEnam->getKey(), $sourceYear->getKey())
                ->whereKey($siswa->getKey())
                ->exists(),
        );
    }

    /**
     * @return array{0: TahunAjaran, 1: TahunAjaran}
     */
    private function createAcademicYears(): array
    {
        $sourceYear = TahunAjaran::query()->create([
            'nama' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => false,
        ]);
        $targetYear = TahunAjaran::query()->create([
            'nama' => '2027/2028',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);

        return [$sourceYear, $targetYear];
    }
}

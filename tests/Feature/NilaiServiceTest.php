<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\JadwalMengajar;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\NilaiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NilaiServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prepares_a_raport_score_with_kkm_and_predicate(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        [$siswa, $jadwalMengajar] = $this->createAcademicData();

        $data = app(NilaiService::class)->prepareForPersistence([
            'siswa_id' => $siswa->getKey(),
            'jadwal_mengajar_id' => $jadwalMengajar->getKey(),
            'nilai_angka' => 90,
            'deskripsi' => 'Siswa memiliki keterampilan yang sangat baik.',
            'is_submitted' => true,
        ]);

        $this->assertSame(70, $data['kkm']);
        $this->assertSame(90, $data['nilai_angka']);
        $this->assertSame(90, $data['nilai_akhir']);
        $this->assertSame('A', $data['predikat']);
        $this->assertTrue($data['is_submitted']);
    }

    public function test_it_uses_the_pts_predicate_thresholds(): void
    {
        $service = app(NilaiService::class);

        $this->assertSame('A', $service->determinePredikat(90, 70));
        $this->assertSame('B', $service->determinePredikat(85, 70));
        $this->assertSame('C', $service->determinePredikat(75, 70));
        $this->assertSame('D', $service->determinePredikat(69, 70));
    }

    public function test_it_rejects_a_student_from_a_different_class(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        [, $jadwalMengajar] = $this->createAcademicData();
        $kelasLain = Kelas::query()->create([
            'nama_kelas' => 'XII IPA 2',
            'tingkat' => 12,
        ]);
        $siswaLain = Siswa::query()->create([
            'nisn' => '1234567890',
            'nama_lengkap' => 'Siswa Kelas Lain',
            'kelas_id' => $kelasLain->getKey(),
        ]);

        $this->expectException(ValidationException::class);

        app(NilaiService::class)->prepareForPersistence([
            'siswa_id' => $siswaLain->getKey(),
            'jadwal_mengajar_id' => $jadwalMengajar->getKey(),
            'nilai_angka' => 80,
            'deskripsi' => 'Deskripsi.',
            'is_submitted' => true,
        ]);
    }

    public function test_it_prevents_duplicate_grade_records_for_the_same_schedule(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        [$siswa, $jadwalMengajar] = $this->createAcademicData();

        Nilai::query()->create([
            'siswa_id' => $siswa->getKey(),
            'jadwal_mengajar_id' => $jadwalMengajar->getKey(),
            'kkm' => 70,
            'nilai_angka' => 80,
            'predikat' => 'B',
            'nilai_akhir' => 80,
            'is_submitted' => true,
        ]);

        $this->expectException(ValidationException::class);

        app(NilaiService::class)->prepareForPersistence([
            'siswa_id' => $siswa->getKey(),
            'jadwal_mengajar_id' => $jadwalMengajar->getKey(),
            'nilai_angka' => 75,
            'deskripsi' => 'Deskripsi.',
            'is_submitted' => true,
        ]);
    }

    /**
     * @return array{0: Siswa, 1: JadwalMengajar}
     */
    private function createAcademicData(): array
    {
        $kelas = Kelas::query()->create([
            'nama_kelas' => 'XII IPA 1',
            'tingkat' => 12,
        ]);
        $siswa = Siswa::query()->create([
            'nisn' => '1234567891',
            'nama_lengkap' => 'Siswa Uji',
            'kelas_id' => $kelas->getKey(),
        ]);
        $guru = Guru::query()->create([
            'user_id' => User::factory()->create(['role' => User::ROLE_GURU])->getKey(),
            'nip' => '198001012000011001',
            'can_input_nilai' => true,
        ]);
        $mataPelajaran = MataPelajaran::query()->create([
            'kode_mapel' => 'MAT',
            'nama_mapel' => 'Matematika',
            'kelompok' => 'A',
            'kkm' => 70,
        ]);
        $tahunAjaran = TahunAjaran::query()->create([
            'nama' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);
        $jadwalMengajar = JadwalMengajar::query()->create([
            'guru_id' => $guru->getKey(),
            'mapel_id' => $mataPelajaran->getKey(),
            'kelas_id' => $kelas->getKey(),
            'tahun_ajaran_id' => $tahunAjaran->getKey(),
        ]);

        return [$siswa, $jadwalMengajar];
    }
}

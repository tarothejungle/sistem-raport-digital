<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\AbsenSiswa;
use App\Models\Guru;
use App\Models\JadwalMengajar;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\AbsensiSiswaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbsensiSiswaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_saves_attendance_once_for_a_class_and_school_year(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        [$kelas, $tahun, $siswa] = $this->academicData();

        $count = app(AbsensiSiswaService::class)->save($kelas, $tahun, [[
            'siswa_id' => $siswa->getKey(),
            'sakit' => 2,
            'izin' => 1,
            'alpa' => 3,
        ]]);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('absensi_siswa', [
            'siswa_id' => $siswa->getKey(),
            'tahun_ajaran_id' => $tahun->getKey(),
            'sakit' => 2,
            'izin' => 1,
            'alpa' => 3,
        ]);
    }

    public function test_teacher_can_only_open_attendance_for_an_assigned_class(): void
    {
        [$kelas, $tahun, , $guru] = $this->academicData();
        $this->actingAs($guru->user);

        app(AbsensiSiswaService::class)->ensureActorCanManageClass($kelas, $tahun);

        $this->assertTrue(AbsenSiswa::canAccess());
        $this->get(AbsenSiswa::getUrl())
            ->assertOk()
            ->assertSee('Absen Siswa')
            ->assertSee('Pilih Kelas')
            ->assertSee('raport-absen-siswa-table', false)
            ->assertSee('raport-absen-siswa-modal', false)
            ->assertSee('raport-absen-siswa-focus', false);
    }

    /** @return array{0: Kelas, 1: TahunAjaran, 2: Siswa, 3: Guru} */
    private function academicData(): array
    {
        $guru = Guru::query()->create([
            'user_id' => User::factory()->create(['role' => User::ROLE_GURU])->getKey(),
            'can_input_nilai' => true,
        ]);
        $kelas = Kelas::query()->create(['nama_kelas' => '5A', 'tingkat' => 5]);
        $tahun = TahunAjaran::query()->create([
            'nama' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);
        $mapel = MataPelajaran::query()->create([
            'kode_mapel' => 'MTK',
            'nama_mapel' => 'Matematika',
            'kelompok' => 'A',
            'kkm' => 70,
        ]);
        JadwalMengajar::query()->create([
            'guru_id' => $guru->getKey(),
            'mapel_id' => $mapel->getKey(),
            'kelas_id' => $kelas->getKey(),
            'tahun_ajaran_id' => $tahun->getKey(),
        ]);
        $siswa = Siswa::query()->create([
            'nisn' => '1234567890',
            'nama_lengkap' => 'Budi Santoso',
            'kelas_id' => $kelas->getKey(),
            'status' => Siswa::STATUS_AKTIF,
        ]);

        return [$kelas, $tahun, $siswa, $guru];
    }
}

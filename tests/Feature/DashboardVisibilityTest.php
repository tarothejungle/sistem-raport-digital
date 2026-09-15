<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\InputNilaiPerMapel;
use App\Filament\Admin\Widgets\RaportCommandCenter;
use App\Models\Guru;
use App\Models\JadwalMengajar;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_the_command_center_widget(): void
    {
        $this->assertFalse(RaportCommandCenter::canView());
    }

    public function test_authenticated_user_can_view_the_command_center_widget(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_GURU]));

        $this->assertTrue(RaportCommandCenter::canView());
    }

    public function test_a_student_never_receives_class_progress_rows(): void
    {
        $this->seedAcademicData();
        $siswaUser = User::factory()->create([
            'role' => User::ROLE_SISWA,
            'username' => 'siswa.portal',
        ]);
        Siswa::query()->create([
            'nisn' => '2000000001',
            'nama_lengkap' => 'Siswa Portal',
            'kelas_id' => Kelas::query()->value('id'),
            'user_id' => $siswaUser->getKey(),
            'can_view_nilai' => true,
        ]);
        $this->actingAs($siswaUser);

        $data = $this->viewDataOf(new RaportCommandCenter);

        $this->assertSame([], $data['progressRows']);
        $this->assertFalse($data['showProgressPanel']);
        $this->assertSame('Ringkasan Akademik Saya', $data['heroTitle']);
    }

    public function test_an_admin_still_receives_class_progress_rows(): void
    {
        $this->seedAcademicData();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $data = $this->viewDataOf(new RaportCommandCenter);

        $this->assertNotSame([], $data['progressRows']);
        $this->assertTrue($data['showProgressPanel']);
        $this->assertSame('Status Kesiapan Rapor', $data['heroTitle']);
    }

    public function test_a_teacher_without_input_permission_sees_no_schedule_navigation(): void
    {
        [$guru] = $this->seedAcademicData();
        $guru->update(['can_input_nilai' => false]);
        $this->actingAs($guru->user);

        $this->assertTrue(InputNilaiPerMapel::navigasiMataPelajaran()->isEmpty());
    }

    public function test_a_teacher_with_input_permission_sees_their_schedule_navigation(): void
    {
        [$guru] = $this->seedAcademicData();
        $this->actingAs($guru->user);

        $this->assertCount(1, InputNilaiPerMapel::navigasiMataPelajaran());
    }

    /**
     * @return array<string, mixed>
     */
    private function viewDataOf(RaportCommandCenter $widget): array
    {
        $method = new \ReflectionMethod($widget, 'getViewData');
        $method->setAccessible(true);

        /** @var array<string, mixed> $data */
        $data = $method->invoke($widget);

        return $data;
    }

    /**
     * @return array{0: Guru, 1: JadwalMengajar}
     */
    private function seedAcademicData(): array
    {
        $guru = Guru::query()->create([
            'user_id' => User::factory()->create([
                'role' => User::ROLE_GURU,
                'username' => 'guru.dashboard',
            ])->getKey(),
            'can_input_nilai' => true,
        ]);
        $kelas = Kelas::query()->create([
            'nama_kelas' => 'XII IPA 1',
            'tingkat' => 12,
            'wali_kelas_id' => $guru->getKey(),
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

        return [$guru, $jadwalMengajar];
    }
}

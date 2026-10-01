<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Auth\EditProfile;
use App\Filament\Admin\Resources\NilaiResource;
use App\Filament\Admin\Resources\PengaturanMadrasahResource;
use App\Models\Guru;
use App\Models\JadwalMengajar;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\NilaiService;
use App\Services\NilaiSpreadsheetService;
use App\Services\StudentAccessService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_cannot_export_historical_leger_or_raport(): void
    {
        [$guru, $kelas, $tahun, $siswa] = $this->academicData(active: false);

        $this->actingAs($guru->user)
            ->get(route('admin.leger-pts.xlsx', ['kelas' => $kelas, 'tahunAjaran' => $tahun]))
            ->assertForbidden();

        $this->get(route('admin.rapor.download', ['siswa' => $siswa, 'tahunAjaran' => $tahun]))
            ->assertForbidden();
    }

    public function test_historical_schedule_cannot_change_student_portal_access(): void
    {
        [$guru, , , $siswa] = $this->academicData(active: false);

        $this->expectException(AuthorizationException::class);

        app(StudentAccessService::class)->setViewingAccess($siswa, true, $guru->user);
    }

    public function test_teacher_cannot_manage_a_historical_grade_schedule(): void
    {
        [$guru, , , $siswa, $jadwal] = $this->academicData(active: false);
        $this->actingAs($guru->user);

        $this->expectException(AuthorizationException::class);

        app(NilaiService::class)->prepareForPersistence([
            'siswa_id' => $siswa->getKey(),
            'jadwal_mengajar_id' => $jadwal->getKey(),
            'nilai_angka' => 80,
            'deskripsi' => 'Percobaan perubahan nilai historis.',
            'is_submitted' => true,
        ]);
    }

    public function test_teacher_cannot_resolve_another_teachers_schedule_from_tampered_form_state(): void
    {
        [$guru, , , , $jadwalLain] = $this->academicData(active: true);
        $guruLain = Guru::query()->create([
            'user_id' => User::factory()->create(['role' => User::ROLE_GURU])->getKey(),
            'can_input_nilai' => true,
        ]);
        $jadwalLain->update(['guru_id' => $guruLain->getKey()]);
        $this->actingAs($guru->user);

        $method = new \ReflectionMethod(NilaiResource::class, 'resolveAvailableJadwal');

        $this->assertNull($method->invoke(null, $jadwalLain->getKey()));
    }

    public function test_leger_export_writes_formula_like_student_data_as_text(): void
    {
        [, , , $siswa, $jadwal] = $this->academicData(active: true);
        $siswa->update(['nama_lengkap' => '=HYPERLINK("https://example.test","x")']);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $response = app(NilaiSpreadsheetService::class)->template($jadwal);
        $path = $response->getFile()->getPathname();
        $reader = new Reader;
        $reader->open($path);
        $rows = [];

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rows[] = $row->toArray();
                }

                break;
            }
        } finally {
            $reader->close();
            @unlink($path);
        }

        $studentRow = collect($rows)->first(
            static fn (array $row): bool => ($row[0] ?? null) === '9000000001',
        );

        $this->assertIsArray($studentRow);
        $this->assertSame('=HYPERLINK("https://example.test","x")', $studentRow[1]);
    }

    public function test_signature_upload_uses_private_disk_and_profile_requires_current_password_for_email_changes(): void
    {
        $resourceSource = file_get_contents(app_path('Filament/Admin/Resources/PengaturanMadrasahResource.php'));
        $profileSource = file_get_contents(app_path('Filament/Admin/Pages/Auth/EditProfile.php'));

        $this->assertIsString($resourceSource);
        $this->assertStringContainsString("FileUpload::make('ttd_kepala_path')", $resourceSource);
        $this->assertMatchesRegularExpression(
            "/FileUpload::make\('ttd_kepala_path'\).*?->disk\('local'\).*?->visibility\('private'\)/s",
            $resourceSource,
        );
        $this->assertIsString($profileSource);
        $this->assertStringContainsString('$this->getCurrentPasswordFormComponent()', $profileSource);

        $this->assertTrue(EditProfile::canAccess());
        $this->assertSame(PengaturanMadrasahResource::class, PengaturanMadrasahResource::class);
    }

    /** @return array{Guru, Kelas, TahunAjaran, Siswa, JadwalMengajar} */
    private function academicData(bool $active): array
    {
        $guru = Guru::query()->create([
            'user_id' => User::factory()->create(['role' => User::ROLE_GURU])->getKey(),
            'can_input_nilai' => true,
        ]);
        $kelas = Kelas::query()->create([
            'nama_kelas' => '6A Security',
            'tingkat' => 6,
            'wali_kelas_id' => $guru->getKey(),
        ]);
        $tahun = TahunAjaran::query()->create([
            'nama' => $active ? '2026/2027' : '2025/2026',
            'semester' => 'Ganjil',
            'is_active' => $active,
        ]);
        $mapel = MataPelajaran::query()->create([
            'kode_mapel' => 'SEC',
            'nama_mapel' => 'Security',
            'kelompok' => 'A',
        ]);
        $jadwal = JadwalMengajar::query()->create([
            'guru_id' => $guru->getKey(),
            'mapel_id' => $mapel->getKey(),
            'kelas_id' => $kelas->getKey(),
            'tahun_ajaran_id' => $tahun->getKey(),
        ]);
        $siswa = Siswa::query()->create([
            'nisn' => '9000000001',
            'nama_lengkap' => 'Siswa Security',
            'kelas_id' => $kelas->getKey(),
            'status' => Siswa::STATUS_AKTIF,
        ]);

        return [$guru, $kelas, $tahun, $siswa, $jadwal];
    }
}

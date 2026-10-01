<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Resources\GuruResource;
use App\Filament\Admin\Resources\NilaiSiswaResource;
use App\Filament\Admin\Resources\PengaturanMadrasahResource\Pages\ListPengaturanMadrasah;
use App\Filament\Admin\Resources\SiswaResource;
use App\Filament\Admin\Resources\SiswaResource\Pages\ListSiswas;
use App\Models\Guru;
use App\Models\JadwalMengajar;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileBottomNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_mobile_navigation_contains_direct_and_grouped_menus(): void
    {
        $response = $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(Dashboard::getUrl())
            ->assertOk()
            ->assertSee('Navigasi utama mobile')
            ->assertDontSee('Perluas atau ciutkan navigation bar')
            ->assertSee('Sistem Rapor Digital')
            ->assertSee('Dashboard')
            ->assertSee('Pengaturan')
            ->assertSee('Pengaturan Madrasah')
            ->assertSee('Isi Pengaturan Madrasah')
            ->assertSee('Maintenance Website')
            ->assertSee('Pengumuman Website')
            ->assertSee('Master Data')
            ->assertSee('Data Guru')
            ->assertSee('List Data Guru')
            ->assertSee('Sync Guru')
            ->assertSee('Tambah Guru')
            ->assertSee('Data Siswa')
            ->assertSee('List Data Siswa')
            ->assertSee('Tambah Siswa')
            ->assertSee('Template Excel')
            ->assertSee('Import Excel')
            ->assertSee('Import Siswa (.json)')
            ->assertSee('List Data Kelas')
            ->assertSee('Tambah Semua Kelas')
            ->assertSee('Data Alumni')
            ->assertDontSee('List Data Alumni')
            ->assertSee('List Mata Pelajaran')
            ->assertSee('Tambah Mata Pelajaran')
            ->assertSee('List Tahun Ajaran')
            ->assertSee('Set Tahun Ajaran')
            ->assertSee('Kembali ke Master Data')
            ->assertSee('Akademik')
            ->assertSee('Pengajar Kelas')
            ->assertSee('List Pengajar Kelas')
            ->assertSee('Atur Pengajar Kelas')
            ->assertSee('Kenaikan Kelas')
            ->assertSee('List Absen Siswa')
            ->assertSee('Isi Absen Siswa')
            ->assertSee('Leger Nilai PTS')
            ->assertSee('Akses Nilai Siswa')
            ->assertSee('Cetak Rapor Siswa')
            ->assertDontSee('Kelola Kenaikan Kelas')
            ->assertDontSee('Buka Leger Nilai PTS')
            ->assertDontSee('Kelola Akses Nilai Siswa')
            ->assertDontSee('Kelola Cetak Rapor')
            ->assertSee('Kembali ke Akademik');

        $response->assertSeeInOrder(['Pengaturan', 'Master Data']);

        $theme = file_get_contents(resource_path('css/filament/admin/raport-theme.css'));
        $this->assertIsString($theme);
        $this->assertStringContainsString('.fi-topbar-open-sidebar-btn,', $theme);
        $this->assertStringContainsString('.fi-main-sidebar {', $theme);

        $navigation = file_get_contents(resource_path('views/components/mobile-bottom-nav.blade.php'));
        $this->assertIsString($navigation);
        $this->assertStringContainsString("window.addEventListener('scroll'", $navigation);
        $this->assertStringContainsString('this.visible = false', $navigation);
        $this->assertStringContainsString('this.visible = true', $navigation);
        $this->assertStringContainsString('activeMasterMenu', $navigation);
        $this->assertStringContainsString('activeAcademicMenu', $navigation);
        $this->assertStringContainsString('inputNilaiStep', $navigation);
        $this->assertStringContainsString('activeInputSchedule', $navigation);
        $this->assertStringContainsString('Kembali ke Input Nilai', $navigation);
        $this->assertStringContainsString('Kembali ke Daftar Mapel', $navigation);
        $this->assertStringNotContainsString('raport-mobile-nav__toggle', $navigation);
        $this->assertStringContainsString('raport-input-nilai-modal-active', $navigation);
        $this->assertStringNotContainsString('this.visible = true\n                return', $navigation);
        $this->assertStringContainsString('body.raport-input-nilai-modal-active .raport-mobile-nav', $theme);
        $this->assertStringContainsString('?tableAction=importNilaiSiswa', $navigation);
        $this->assertStringContainsString('?tableAction=tambahNilai', $navigation);
        $this->assertStringContainsString("'trigger' => \$isAbsenSiswaPage ? 'raport-isi-absensi-action' : null", $navigation);
        $this->assertStringContainsString("'trigger' => \$isSiswaPage ? 'raport-import-siswa-json-action' : null", $navigation);
        $this->assertStringContainsString("document.getElementById(@js(\$action['trigger']))", $navigation);
        $this->assertStringContainsString('html.raport-absen-siswa-focus .raport-mobile-nav', $theme);
        $this->assertStringContainsString('body.raport-siswa-json-modal-active .raport-mobile-nav', $theme);
        $this->assertStringContainsString('raport-siswa-json-modal-active', $navigation);

        $absenSiswaPage = file_get_contents(app_path('Filament/Admin/Pages/AbsenSiswa.php'));
        $this->assertIsString($absenSiswaPage);
        $this->assertStringContainsString("'id' => 'raport-isi-absensi-action'", $absenSiswaPage);
        $this->assertStringContainsString("'class' => 'raport-desktop-only-action'", $absenSiswaPage);

        $mobileTopbar = file_get_contents(resource_path('views/components/mobile-topbar-brand.blade.php'));
        $this->assertIsString($mobileTopbar);
        $this->assertStringContainsString("window.matchMedia('(max-width: 767px)').matches && \$store.sidebar.close()", $mobileTopbar);
        $this->assertStringNotContainsString('x-init="$store.sidebar.close()"', $mobileTopbar);

        $this->assertStringContainsString('.raport-desktop-only-action', $theme);
        $this->assertStringContainsString('.raport-mobile-full-search-table .fi-ta-header-toolbar', $theme);
        $this->assertStringContainsString('.raport-mobile-grouped-table .fi-ta-grouping-settings', $theme);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1fr) auto auto auto;', $theme);
        $this->assertStringContainsString('.raport-mobile-grouped-table .fi-ta-col-manager-dropdown', $theme);
        $this->assertStringContainsString('.fi-page:has(.raport-mobile-compact-table)', $theme);

        $inputNilaiPage = file_get_contents(app_path('Filament/Admin/Pages/InputNilaiPerMapel.php'));
        $this->assertIsString($inputNilaiPage);
        $this->assertSame(3, substr_count($inputNilaiPage, "'class' => 'raport-desktop-only-action'"));

        $pengajarPage = file_get_contents(app_path('Filament/Admin/Resources/JadwalMengajarResource/Pages/ListJadwalMengajars.php'));
        $this->assertIsString($pengajarPage);
        $this->assertStringContainsString("'class' => 'raport-desktop-only-action'", $pengajarPage);

        $fullSearchTables = [
            'Filament/Admin/Resources/PengaturanMadrasahResource.php',
            'Filament/Admin/Resources/GuruResource.php',
            'Filament/Admin/Resources/SiswaResource.php',
            'Filament/Admin/Resources/KelasResource.php',
            'Filament/Admin/Resources/MataPelajaranResource.php',
            'Filament/Admin/Resources/TahunAjaranResource.php',
            'Filament/Admin/Pages/KenaikanKelas.php',
            'Filament/Admin/Pages/InputNilaiPerMapel.php',
            'Filament/Admin/Resources/AksesNilaiSiswaResource.php',
            'Filament/Admin/Pages/CetakRaporSiswa.php',
        ];

        foreach ($fullSearchTables as $path) {
            $source = file_get_contents(app_path($path));
            $this->assertIsString($source);
            $this->assertStringContainsString('raport-mobile-full-search-table', $source, $path);
        }

        $alumniResource = file_get_contents(app_path('Filament/Admin/Resources/AlumniResource.php'));
        $this->assertIsString($alumniResource);
        $this->assertStringContainsString('raport-mobile-compact-table', $alumniResource);

        $jadwalResource = file_get_contents(app_path('Filament/Admin/Resources/JadwalMengajarResource.php'));
        $this->assertIsString($jadwalResource);
        $this->assertStringContainsString('raport-mobile-grouped-table', $jadwalResource);
    }

    public function test_student_import_actions_are_grouped_in_export_download_dropdown(): void
    {
        $page = new ListSiswas;
        $method = new \ReflectionMethod($page, 'getHeaderActions');
        $method->setAccessible(true);

        $actions = $method->invoke($page);
        $this->assertCount(2, $actions);
        $this->assertSame('Export/Download', $actions[0]->getLabel());
        $this->assertSame(
            ['downloadTemplate', 'importExcel', 'importJson'],
            array_map(static fn ($action): string => $action->getName(), $actions[0]->getActions()),
        );
        $this->assertSame('create', $actions[1]->getName());
    }

    public function test_settings_table_has_no_duplicate_create_header_action(): void
    {
        $page = new ListPengaturanMadrasah;
        $method = new \ReflectionMethod($page, 'getHeaderActions');
        $method->setAccessible(true);

        $this->assertSame([], $method->invoke($page));
    }

    public function test_mobile_master_data_action_links_open_their_filament_actions(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(GuruResource::getUrl('index', ['action' => 'tarikDataAbsensi']))
            ->assertOk()
            ->assertSee('tarikDataAbsensi');

        $this->get(SiswaResource::getUrl('index', ['action' => 'importExcel']))
            ->assertOk()
            ->assertSee('importExcel');

        $this->get(SiswaResource::getUrl('index', ['action' => 'importJson']))
            ->assertOk()
            ->assertSee('importJson');
    }

    public function test_teacher_mobile_navigation_only_contains_authorized_academic_menus(): void
    {
        $guru = $this->teacherWithSchedule();

        $this->actingAs($guru->user)
            ->get(Dashboard::getUrl())
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Akademik')
            ->assertSee('Input Nilai')
            ->assertSee('List Nilai Siswa')
            ->assertSee('Matematika - 6A')
            ->assertSee('Template Nilai Siswa')
            ->assertSee('Import Nilai Siswa')
            ->assertSee('Tambah Nilai')
            ->assertSee('Kembali ke Input Nilai')
            ->assertSee('Kembali ke Daftar Mapel')
            ->assertSee('Kembali ke Akademik')
            ->assertDontSee('Data Guru')
            ->assertDontSee('Pengaturan Madrasah');
    }

    public function test_student_mobile_navigation_does_not_expose_staff_routes(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SISWA]);
        $kelas = Kelas::query()->create([
            'nama_kelas' => '1A',
            'tingkat' => 1,
        ]);
        Siswa::query()->create([
            'user_id' => $user->getKey(),
            'nisn' => '1234567890',
            'nama_lengkap' => 'Siswa Mobile',
            'kelas_id' => $kelas->getKey(),
            'can_view_nilai' => true,
        ]);

        $this->actingAs($user)
            ->get(NilaiSiswaResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertDontSee('Data Guru')
            ->assertDontSee('Pengajar Kelas')
            ->assertDontSee('Input Nilai');
    }

    private function teacherWithSchedule(): Guru
    {
        $guru = Guru::query()->create([
            'user_id' => User::factory()->create([
                'role' => User::ROLE_GURU,
            ])->getKey(),
            'can_input_nilai' => true,
        ]);
        $kelas = Kelas::query()->create([
            'nama_kelas' => '6A',
            'tingkat' => 6,
            'wali_kelas_id' => $guru->getKey(),
        ]);
        $mapel = MataPelajaran::query()->create([
            'kode_mapel' => 'MTK',
            'nama_mapel' => 'Matematika',
            'kelompok' => 'A',
        ]);
        $tahun = TahunAjaran::query()->create([
            'nama' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);

        JadwalMengajar::query()->create([
            'guru_id' => $guru->getKey(),
            'mapel_id' => $mapel->getKey(),
            'kelas_id' => $kelas->getKey(),
            'tahun_ajaran_id' => $tahun->getKey(),
        ]);

        return $guru;
    }
}

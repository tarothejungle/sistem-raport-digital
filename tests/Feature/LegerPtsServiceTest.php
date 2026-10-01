<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\LegerNilaiPts;
use App\Models\CatatanRapor;
use App\Models\Guru;
use App\Models\JadwalMengajar;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\LegerPtsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;
use ZipArchive;

class LegerPtsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_totals_competition_rankings_and_suggestions_from_eleven_pts_subjects(): void
    {
        [$kelas, $tahunAjaran, $jadwals] = $this->createAcademicData();
        $siswas = collect([
            ['nama' => 'Siswa Peringkat Satu', 'nilai' => 90],
            ['nama' => 'Siswa Peringkat Dua A', 'nilai' => 80],
            ['nama' => 'Siswa Peringkat Dua B', 'nilai' => 80],
            ['nama' => 'Siswa Peringkat Empat', 'nilai' => 70],
        ])->map(function (array $data, int $index) use ($kelas, $jadwals): Siswa {
            $siswa = Siswa::query()->create([
                'nisn' => '100000000'.($index + 1),
                'nama_lengkap' => $data['nama'],
                'kelas_id' => $kelas->getKey(),
            ]);

            foreach ($jadwals as $jadwal) {
                Nilai::query()->create([
                    'siswa_id' => $siswa->getKey(),
                    'jadwal_mengajar_id' => $jadwal->getKey(),
                    'kkm' => 70,
                    'nilai_angka' => $data['nilai'],
                    'predikat' => $data['nilai'] >= 90 ? 'A' : ($data['nilai'] >= 80 ? 'B' : 'C'),
                    'nilai_akhir' => $data['nilai'],
                    'is_submitted' => true,
                ]);
            }

            return $siswa;
        });

        $leger = app(LegerPtsService::class)->build(
            $kelas->getKey(),
            $tahunAjaran->getKey(),
        );
        $rows = collect($leger['rows'])->keyBy('nama_siswa');

        $this->assertTrue($leger['lengkap']);
        $this->assertSame(array_keys(LegerPtsService::MAPEL), array_keys($leger['mapel']));
        $this->assertSame(
            ['AH', 'AA', 'Fiqih', 'PPKN', 'BIn', 'BA', 'SB', 'MTK', 'PJOK', 'Tahfidz', 'BIng'],
            array_column($leger['mapel'], 'label'),
        );
        $this->assertSame(990, $rows['Siswa Peringkat Satu']['jumlah']);
        $this->assertSame('1000000001', $rows['Siswa Peringkat Satu']['nisn']);
        $this->assertSame(1, $rows['Siswa Peringkat Satu']['ranking']);
        $this->assertSame(2, $rows['Siswa Peringkat Dua A']['ranking']);
        $this->assertSame(2, $rows['Siswa Peringkat Dua B']['ranking']);
        $this->assertSame(4, $rows['Siswa Peringkat Empat']['ranking']);
        $this->assertSame(70, $rows['Siswa Peringkat Empat']['mata_pelajaran']['QH']['kkm']);
        $this->assertSame(70, $rows['Siswa Peringkat Empat']['mata_pelajaran']['QH']['nilai_angka']);
        $this->assertSame('Guru Leger', $rows['Siswa Peringkat Empat']['mata_pelajaran']['QH']['guru']);

        CatatanRapor::query()->create([
            'siswa_id' => $siswas->first()->getKey(),
            'tahun_ajaran_id' => $tahunAjaran->getKey(),
            'saran' => 'Pertahankan semangat belajar.',
        ]);

        $rowWithSuggestion = collect(app(LegerPtsService::class)->build(
            $kelas->getKey(),
            $tahunAjaran->getKey(),
        )['rows'])->keyBy('nama_siswa');

        $this->assertSame('Pertahankan semangat belajar.', $rowWithSuggestion['Siswa Peringkat Satu']['saran']);

        Nilai::query()
            ->where('siswa_id', $siswas->last()->getKey())
            ->where('jadwal_mengajar_id', $jadwals->last()->getKey())
            ->delete();

        $incompleteRows = collect(app(LegerPtsService::class)->build(
            $kelas->getKey(),
            $tahunAjaran->getKey(),
        )['rows'])->keyBy('nama_siswa');

        $this->assertNull($incompleteRows['Siswa Peringkat Empat']['jumlah']);
        $this->assertNull($incompleteRows['Siswa Peringkat Empat']['ranking']);
    }

    public function test_admin_can_open_the_leger_page_but_students_cannot(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(LegerNilaiPts::getUrl())
            ->assertOk()
            ->assertSee('Leger Nilai PTS');

        $student = User::factory()->create(['role' => User::ROLE_SISWA]);

        $this->actingAs($student)
            ->get(LegerNilaiPts::getUrl())
            ->assertForbidden();
    }

    public function test_admin_can_download_xlsx_and_pdf_leger_exports(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$kelas, $tahunAjaran] = $this->createAcademicData();
        $kelas->update(['nama_kelas' => 'KELAS 1A']);

        $xlsx = $this->actingAs($admin)->get(route('admin.leger-pts.xlsx', [
            'kelas' => $kelas,
            'tahunAjaran' => $tahunAjaran,
        ]));
        $xlsx->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $xlsx->headers->get('content-type'),
        );
        $xlsxContent = $xlsx->streamedContent();
        $this->assertStringStartsWith('PK', $xlsxContent);
        $this->assertStyledLegerWorkbook($xlsxContent);

        $pdf = $this->actingAs($admin)->get(route('admin.leger-pts.pdf', [
            'kelas' => $kelas,
            'tahunAjaran' => $tahunAjaran,
        ]));
        $pdf->assertOk();
        $pdf->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    private function assertStyledLegerWorkbook(string $content): void
    {
        $path = tempnam(sys_get_temp_dir(), 'leger-pts-');
        file_put_contents($path, $content);

        $archive = new ZipArchive;

        try {
            $this->assertTrue($archive->open($path));
            $worksheet = $archive->getFromName('xl/worksheets/sheet1.xml');
            $styles = $archive->getFromName('xl/styles.xml');

            $this->assertIsString($worksheet);
            $this->assertIsString($styles);
            $this->assertStringContainsString('ref="A1:Q1"', $worksheet);
            $this->assertStringContainsString('state="frozen"', $worksheet);
            $this->assertStringContainsString('LEGER KELAS 1A', $worksheet);
            $this->assertStringContainsString('MADRASAH IBTIDAIYAH LANTABURO', $worksheet);
            $this->assertStringContainsString('TAHUN AJARAN 2025/2026', $worksheet);
            $this->assertStringContainsString('BIn', $worksheet);
            $this->assertStringContainsString('BIng', $worksheet);
            $this->assertStringContainsString('Tahfidz', $worksheet);
            $this->assertStringContainsString('Saran-saran', $worksheet);
            $this->assertStringNotContainsString('KELAS KELAS 1A', $worksheet);
            $this->assertStringContainsString('<borders count="2">', $styles);
            $this->assertStringContainsString('<fgColor rgb="0F3D5E"/>', $styles);
        } finally {
            $archive->close();
            @unlink($path);
        }
    }

    /**
     * @return array{0: Kelas, 1: TahunAjaran, 2: Collection<int, JadwalMengajar>}
     */
    private function createAcademicData(): array
    {
        $kelas = Kelas::query()->create([
            'nama_kelas' => '1A',
            'tingkat' => 1,
        ]);
        $tahunAjaran = TahunAjaran::query()->create([
            'nama' => '2025/2026',
            'semester' => 'Genap',
            'is_active' => true,
        ]);
        $guru = Guru::query()->create([
            'user_id' => User::factory()->create([
                'name' => 'Guru Leger',
                'role' => User::ROLE_GURU,
            ])->getKey(),
            'can_input_nilai' => true,
        ]);

        $jadwals = collect(LegerPtsService::MAPEL)
            ->map(function (array $definition, string $kode) use ($kelas, $tahunAjaran, $guru): JadwalMengajar {
                $mapel = MataPelajaran::query()->create([
                    'kode_mapel' => $kode,
                    'nama_mapel' => $definition['nama'],
                    'kelompok' => $definition['kelompok'],
                ]);

                return JadwalMengajar::query()->create([
                    'guru_id' => $guru->getKey(),
                    'mapel_id' => $mapel->getKey(),
                    'kelas_id' => $kelas->getKey(),
                    'tahun_ajaran_id' => $tahunAjaran->getKey(),
                ]);
            })
            ->values();

        return [$kelas, $tahunAjaran, $jadwals];
    }
}

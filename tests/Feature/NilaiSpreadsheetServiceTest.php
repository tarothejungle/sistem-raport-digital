<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\InputNilaiPerMapel;
use App\Filament\Admin\Resources\GuruResource;
use App\Models\CatatanRapor;
use App\Models\Guru;
use App\Models\JadwalMengajar;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\NilaiSpreadsheetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class NilaiSpreadsheetServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_download_schedule_template_with_student_roster(): void
    {
        [$guru, $jadwal, $siswa] = $this->academicData();
        $this->actingAs($guru->user);

        $response = $this->get(route('admin.nilai.template', [
            'jadwalMengajar' => $jadwal,
        ]));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $response->headers->get('content-type'),
        );

        $path = tempnam(sys_get_temp_dir(), 'nilai-template-');
        file_put_contents($path, $response->streamedContent());

        try {
            $rows = $this->xlsxRows($path);
            $this->assertSame('TEMPLATE NILAI SISWA', $rows[0][0]);
            $this->assertSame('Deskripsi', $rows[6][0]);
            $this->assertSame(['NISN', 'Nama Siswa', 'Nilai', 'Saran'], $rows[8]);
            $this->assertSame($siswa->nisn, (string) $rows[9][0]);
            $this->assertSame($siswa->nama_lengkap, $rows[9][1]);
        } finally {
            @unlink($path);
        }
    }

    public function test_teacher_cannot_download_another_teachers_template(): void
    {
        [, $jadwal] = $this->academicData();
        $otherGuru = Guru::query()->create([
            'user_id' => User::factory()->create([
                'role' => User::ROLE_GURU,
            ])->getKey(),
            'jenis_kelamin' => 'P',
            'can_input_nilai' => true,
        ]);

        $this->actingAs($otherGuru->user)
            ->get(route('admin.nilai.template', [
                'jadwalMengajar' => $jadwal,
            ]))
            ->assertForbidden();
    }

    public function test_import_saves_scores_descriptions_and_suggestions_from_schedule_template_rows(): void
    {
        [$guru, $jadwal, $siswa] = $this->academicData();
        $this->actingAs($guru->user);
        $rows = $this->templateRows($siswa, 88, 'proyek pecahan', 'Pertahankan ketelitian.');

        $count = app(NilaiSpreadsheetService::class)->importRows($jadwal, $rows);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('nilai', [
            'siswa_id' => $siswa->getKey(),
            'jadwal_mengajar_id' => $jadwal->getKey(),
            'kkm' => 75,
            'nilai_angka' => 88,
            'nilai_akhir' => 88,
            'is_submitted' => true,
        ]);
        $this->assertStringContainsString(
            'proyek pecahan',
            (string) Nilai::query()->value('deskripsi'),
        );
        $this->assertDatabaseHas('catatan_rapor', [
            'siswa_id' => $siswa->getKey(),
            'tahun_ajaran_id' => $jadwal->tahun_ajaran_id,
            'saran' => 'Pertahankan ketelitian.',
        ]);
    }

    public function test_import_rejects_student_outside_the_schedule_without_partial_changes(): void
    {
        [$guru, $jadwal, $siswa] = $this->academicData();
        $this->actingAs($guru->user);
        $rows = $this->templateRows($siswa, 88, 'proyek pecahan', 'Terus belajar.');
        $rows[9][0] = '9999999999';

        try {
            app(NilaiSpreadsheetService::class)->importRows($jadwal, $rows);
            $this->fail('Import seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString(
                'bukan siswa pada kelas',
                collect($exception->errors())->flatten()->implode(' '),
            );
        }

        $this->assertSame(0, Nilai::query()->count());
        $this->assertSame(0, CatatanRapor::query()->count());
    }

    public function test_import_rejects_template_from_another_subject(): void
    {
        [$guru, $jadwal, $siswa] = $this->academicData();
        $this->actingAs($guru->user);
        $rows = $this->templateRows($siswa, 88, 'proyek pecahan', 'Terus belajar.');
        $rows[1][0] = 'BAHASA INDONESIA';

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Template bukan milik mata pelajaran');

        app(NilaiSpreadsheetService::class)->importRows($jadwal, $rows);
    }

    public function test_import_accepts_unchanged_header_with_trailing_empty_spreadsheet_cells(): void
    {
        [$guru, $jadwal, $siswa] = $this->academicData();
        $this->actingAs($guru->user);
        $rows = $this->templateRows($siswa, 88, 'proyek pecahan', 'Terus belajar.');
        $rows[8][] = null;

        $count = app(NilaiSpreadsheetService::class)->importRows($jadwal, $rows);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('nilai', [
            'siswa_id' => $siswa->getKey(),
            'jadwal_mengajar_id' => $jadwal->getKey(),
            'nilai_angka' => 88,
        ]);
    }

    public function test_import_still_rejects_an_extra_non_empty_header(): void
    {
        [$guru, $jadwal, $siswa] = $this->academicData();
        $this->actingAs($guru->user);
        $rows = $this->templateRows($siswa, 88, 'proyek pecahan', 'Terus belajar.');
        $rows[8][] = 'Kolom Tambahan';

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Header file berubah');

        app(NilaiSpreadsheetService::class)->importRows($jadwal, $rows);
    }

    public function test_input_page_displays_spreadsheet_actions_description_and_suggestion_columns(): void
    {
        [$guru, $jadwal] = $this->academicData();
        $this->actingAs($guru->user)
            ->get(InputNilaiPerMapel::getUrl([
                'jadwalMengajar' => $jadwal,
            ]))
            ->assertOk()
            ->assertSee('Template Nilai Siswa')
            ->assertSee('Import Nilai Siswa')
            ->assertSeeInOrder(['Deskripsi', 'Saran'])
            ->assertDontSee('Sakit')
            ->assertDontSee('Izin')
            ->assertDontSee('Alpa');
    }

    public function test_guru_index_uses_alphabetical_default_sort(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        foreach (['Zainal', 'Ahmad'] as $name) {
            Guru::query()->create([
                'user_id' => User::factory()->create([
                    'name' => $name,
                    'role' => User::ROLE_GURU,
                ])->getKey(),
                'jenis_kelamin' => 'L',
            ]);
        }

        $this->actingAs($admin)
            ->get(GuruResource::getUrl('index'))
            ->assertOk()
            ->assertSeeInOrder(['Ahmad', 'Zainal']);
    }

    /**
     * @return array{0: Guru, 1: JadwalMengajar, 2: Siswa}
     */
    private function academicData(): array
    {
        $guru = Guru::query()->create([
            'user_id' => User::factory()->create([
                'name' => 'Guru Nilai',
                'role' => User::ROLE_GURU,
            ])->getKey(),
            'jenis_kelamin' => 'L',
            'can_input_nilai' => true,
        ]);
        $kelas = Kelas::query()->create([
            'nama_kelas' => '5A',
            'tingkat' => 5,
        ]);
        $mapel = MataPelajaran::query()->create([
            'kode_mapel' => 'MTK',
            'nama_mapel' => 'Matematika',
            'kelompok' => 'A',
            'kkm' => 70,
        ]);
        $tahun = TahunAjaran::query()->create([
            'nama' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);
        $jadwal = JadwalMengajar::query()->create([
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

        return [$guru, $jadwal, $siswa];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function templateRows(Siswa $siswa, int $nilai, string $deskripsi, string $saran): array
    {
        return [
            ['TEMPLATE NILAI SISWA'],
            ['MATEMATIKA'],
            ['KELAS 5A'],
            ['TAHUN AJARAN 2026/2027 - GANJIL'],
            [],
            ['KKM', 75],
            ['Deskripsi', $deskripsi],
            [],
            ['NISN', 'Nama Siswa', 'Nilai', 'Saran'],
            [$siswa->nisn, $siswa->nama_lengkap, $nilai, $saran],
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function xlsxRows(string $path): array
    {
        $options = new Options;
        $options->SHOULD_PRESERVE_EMPTY_ROWS = true;
        $reader = new Reader($options);
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
        }

        return $rows;
    }
}

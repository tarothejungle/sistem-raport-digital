<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Services\SiswaImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SiswaImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_json_preview_accepts_students_without_nisn_without_writing_database(): void
    {
        $kelas = Kelas::query()->create([
            'nama_kelas' => 'Kelas 1A',
            'tingkat' => 1,
        ]);
        $path = $this->jsonFile([
            [
                'id' => 101,
                'full_name' => 'SISWA DENGAN NISN',
                'nisn' => '3190833565',
                'study_group_name' => 'KELAS 1A',
            ],
            [
                'id' => 102,
                'full_name' => 'SISWA TANPA NISN',
                'nisn' => null,
                'study_group_name' => 'KELAS 1A',
            ],
        ]);

        try {
            $preview = app(SiswaImportService::class)->previewJson($path);
        } finally {
            @unlink($path);
        }

        $this->assertCount(2, $preview['students']);
        $this->assertSame(0, $preview['skipped']);
        $this->assertNull($preview['students'][1]['nisn']);
        $this->assertSame($kelas->getKey(), current($preview['suggested_mappings']));
        $this->assertDatabaseCount('siswa', 0);
        $this->assertDatabaseCount('user', 0);
    }

    public function test_it_uses_nested_emis_study_group_name_as_fallback(): void
    {
        $kelas = Kelas::query()->create([
            'nama_kelas' => 'Kelas 2B',
            'tingkat' => 2,
        ]);
        $path = $this->jsonFile([
            [
                'id' => 103,
                'full_name' => 'SISWA DUA',
                'nisn' => '3190833566',
                'learning_activity' => [
                    'study_group' => ['name' => 'KELAS 2B'],
                ],
            ],
        ]);

        try {
            $preview = app(SiswaImportService::class)->previewJson($path);
        } finally {
            @unlink($path);
        }

        $this->assertSame('KELAS 2B', $preview['students'][0]['source_class']);
        $this->assertSame($kelas->getKey(), current($preview['suggested_mappings']));
        $this->assertDatabaseCount('siswa', 0);
    }

    public function test_it_imports_only_selected_students_into_confirmed_registered_class(): void
    {
        $kelas = Kelas::query()->create([
            'nama_kelas' => 'Kelas Aktual',
            'tingkat' => 5,
        ]);
        $path = $this->jsonFile([
            [
                'id' => 104,
                'full_name' => 'SISWA DIPILIH',
                'nisn' => '3190833567',
                'study_group_name' => 'KELAS 5 C',
            ],
            [
                'id' => 105,
                'full_name' => 'SISWA TIDAK DIPILIH',
                'nisn' => '3190833568',
                'study_group_name' => 'KELAS 5 C',
            ],
        ]);

        try {
            $preview = app(SiswaImportService::class)->previewJson($path);
            $sourceKey = array_key_first($preview['source_classes']);
            $summary = app(SiswaImportService::class)->importJsonSelection(
                $path,
                ['emis:104'],
                [$sourceKey => $kelas->getKey()],
            );
        } finally {
            @unlink($path);
        }

        $this->assertSame([
            'created' => 1,
            'updated' => 0,
            'skipped' => 0,
        ], $summary);
        $this->assertDatabaseHas('siswa', [
            'emis_id' => '104',
            'nisn' => '3190833567',
            'kelas_id' => $kelas->getKey(),
        ]);
        $this->assertDatabaseMissing('siswa', ['emis_id' => '105']);
        $this->assertSame('3190833567', Siswa::query()->with('user')->sole()->user?->username);
    }

    public function test_it_imports_student_without_nisn_without_portal_account(): void
    {
        $kelas = Kelas::query()->create([
            'nama_kelas' => 'Kelas 1A',
            'tingkat' => 1,
        ]);
        $path = $this->jsonFile([
            [
                'id' => 106,
                'full_name' => 'SISWA MENUNGGU NISN',
                'nisn' => null,
                'study_group_name' => 'KELAS 1A',
            ],
        ]);

        try {
            $preview = app(SiswaImportService::class)->previewJson($path);
            $sourceKey = array_key_first($preview['source_classes']);
            app(SiswaImportService::class)->importJsonSelection(
                $path,
                ['emis:106'],
                [$sourceKey => $kelas->getKey()],
            );
        } finally {
            @unlink($path);
        }

        $siswa = Siswa::query()->sole();
        $this->assertSame('106', $siswa->emis_id);
        $this->assertNull($siswa->nisn);
        $this->assertNull($siswa->user_id);
        $this->assertNull($siswa->user);
        $this->assertDatabaseCount('user', 0);
    }

    public function test_reimport_adds_account_when_nisn_becomes_available(): void
    {
        $kelas = Kelas::query()->create([
            'nama_kelas' => 'Kelas 1A',
            'tingkat' => 1,
        ]);
        $service = app(SiswaImportService::class);
        $withoutNisn = $this->jsonFile([
            [
                'id' => 107,
                'full_name' => 'SISWA SINKRON',
                'nisn' => null,
                'study_group_name' => 'KELAS 1A',
            ],
        ]);
        $withNisn = $this->jsonFile([
            [
                'id' => 107,
                'full_name' => 'SISWA SINKRON',
                'nisn' => '3190833570',
                'study_group_name' => 'KELAS 1A',
            ],
        ]);

        try {
            $sourceKey = array_key_first($service->previewJson($withoutNisn)['source_classes']);
            $service->importJsonSelection($withoutNisn, ['emis:107'], [$sourceKey => $kelas->getKey()]);
            $summary = $service->importJsonSelection($withNisn, ['emis:107'], [$sourceKey => $kelas->getKey()]);
        } finally {
            @unlink($withoutNisn);
            @unlink($withNisn);
        }

        $this->assertSame(1, $summary['updated']);
        $this->assertDatabaseCount('siswa', 1);
        $siswa = Siswa::query()->with('user')->sole();
        $this->assertSame('3190833570', $siswa->nisn);
        $this->assertSame('3190833570', $siswa->user?->username);
    }

    public function test_it_rejects_confirmation_without_registered_target_class(): void
    {
        $path = $this->jsonFile([
            [
                'id' => 108,
                'full_name' => 'SISWA TANPA TUJUAN',
                'nisn' => null,
                'study_group_name' => 'KELAS 6 D',
            ],
        ]);

        try {
            $preview = app(SiswaImportService::class)->previewJson($path);
            $sourceKey = array_key_first($preview['source_classes']);

            try {
                app(SiswaImportService::class)->importJsonSelection(
                    $path,
                    ['emis:108'],
                    [$sourceKey => 999999],
                );
                $this->fail('Import seharusnya menolak kelas tujuan yang tidak terdaftar.');
            } catch (ValidationException $exception) {
                $this->assertStringContainsString(
                    'Pilih kelas tujuan',
                    collect($exception->errors())->flatten()->implode(' '),
                );
            }
        } finally {
            @unlink($path);
        }

        $this->assertDatabaseCount('siswa', 0);
        $this->assertDatabaseCount('user', 0);
    }

    /**
     * @param  array<int, array<string, mixed>>  $data
     */
    private function jsonFile(array $data): string
    {
        $path = tempnam(sys_get_temp_dir(), 'siswa-emis-');
        $this->assertNotFalse($path);
        file_put_contents($path, json_encode($data, JSON_THROW_ON_ERROR));

        return $path;
    }
}

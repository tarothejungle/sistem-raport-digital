<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Services\KelasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class KelasServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_class_with_normalized_attributes(): void
    {
        $kelas = app(KelasService::class)->create([
            'nama_kelas' => ' Kelas 1A ',
            'tingkat' => '1',
        ]);

        $this->assertSame('Kelas 1A', $kelas->nama_kelas);
        $this->assertSame(1, $kelas->tingkat);
        $this->assertNull($kelas->wali_kelas_id);
    }

    public function test_it_rejects_duplicate_class_names(): void
    {
        Kelas::query()->create([
            'nama_kelas' => 'Kelas 1A',
            'tingkat' => 1,
        ]);

        $this->expectException(ValidationException::class);

        app(KelasService::class)->create([
            'nama_kelas' => 'Kelas 1A',
            'tingkat' => 1,
        ]);
    }

    public function test_it_rejects_deleting_class_with_students(): void
    {
        $kelas = Kelas::query()->create([
            'nama_kelas' => 'Kelas 1A',
            'tingkat' => 1,
        ]);

        Siswa::query()->create([
            'nisn' => '100000020',
            'nama_lengkap' => 'Siswa Terkait',
            'kelas_id' => $kelas->getKey(),
            'status' => Siswa::STATUS_AKTIF,
        ]);

        $this->expectException(ValidationException::class);

        app(KelasService::class)->delete($kelas);
    }
}

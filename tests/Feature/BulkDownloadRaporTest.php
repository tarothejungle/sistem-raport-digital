<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class BulkDownloadRaporTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_bulk_download_selected_raports_as_a_zip_file(): void
    {
        $tahunAjaran = TahunAjaran::query()->create([
            'nama' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);

        $kelas = Kelas::query()->create([
            'nama_kelas' => 'Kelas 2A',
            'tingkat' => 2,
        ]);

        $siswa = Siswa::query()->create([
            'nisn' => '2000000099',
            'nama_lengkap' => 'Siswa Unduh Rapor',
            'kelas_id' => $kelas->getKey(),
        ]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $response = $this->get(
            '/admin/cetak-rapor/'.$tahunAjaran->getKey().'/unduh-terpilih?siswa='.$siswa->getKey(),
        );

        $response->assertOk();

        $baseResponse = $response->baseResponse;

        $this->assertInstanceOf(BinaryFileResponse::class, $baseResponse);
        $this->assertSame('application/zip', $baseResponse->headers->get('Content-Type'));

        $zipPath = $baseResponse->getFile()->getPathname();

        $this->assertFileExists($zipPath);
        $this->assertMatchesRegularExpression('/[0-9a-f-]{36}\.zip$/', str_replace('\\', '/', $zipPath));
        @unlink($zipPath);
    }

    public function test_bulk_download_is_rate_limited_per_user(): void
    {
        $tahunAjaran = TahunAjaran::query()->create([
            'nama' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);
        $kelas = Kelas::query()->create([
            'nama_kelas' => 'Kelas 2A',
            'tingkat' => 2,
        ]);
        $siswa = Siswa::query()->create([
            'nisn' => '2000000098',
            'nama_lengkap' => 'Siswa Rate Limit',
            'kelas_id' => $kelas->getKey(),
        ]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $url = route('admin.rapor.bulk-download', [
            'tahunAjaran' => $tahunAjaran,
            'siswa' => $siswa->getKey(),
        ]);

        foreach (range(1, 2) as $_) {
            $response = $this->get($url)->assertOk();
            @unlink($response->baseResponse->getFile()->getPathname());
        }

        $this->get($url)->assertTooManyRequests();
    }
}

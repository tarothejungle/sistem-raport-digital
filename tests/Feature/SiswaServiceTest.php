<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Services\SiswaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SiswaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_student_with_requested_login_identity(): void
    {
        $kelas = $this->createKelas();

        $siswa = app(SiswaService::class)->create([
            'nisn' => '100000010',
            'nama_lengkap' => 'Siswa Akun',
            'kelas_id' => $kelas->getKey(),
            'username' => 'Siswa.001',
            'email' => 'Siswa.001@example.test',
            'password' => 'password-siswa',
        ]);

        $siswa->load('user');

        $this->assertSame(Siswa::STATUS_AKTIF, $siswa->status);
        $this->assertSame('siswa.001', $siswa->user?->username);
        $this->assertSame('siswa.001@example.test', $siswa->user?->email);
        $this->assertTrue(Hash::check('password-siswa', (string) $siswa->user?->password));
    }

    public function test_it_updates_student_login_identity_without_requiring_new_password(): void
    {
        $kelas = $this->createKelas();

        $siswa = app(SiswaService::class)->create([
            'nisn' => '100000011',
            'nama_lengkap' => 'Siswa Lama',
            'kelas_id' => $kelas->getKey(),
            'username' => 'siswa-lama',
            'email' => 'siswa-lama@example.test',
            'password' => 'password-siswa',
        ]);

        app(SiswaService::class)->update($siswa, [
            'nisn' => '100000011',
            'nama_lengkap' => 'Siswa Baru',
            'kelas_id' => $kelas->getKey(),
            'username' => 'Siswa.Baru',
            'email' => 'Siswa.Baru@example.test',
        ]);

        $siswa->refresh()->load('user');

        $this->assertSame('Siswa Baru', $siswa->nama_lengkap);
        $this->assertSame('siswa.baru', $siswa->user?->username);
        $this->assertSame('siswa.baru@example.test', $siswa->user?->email);
        $this->assertTrue(Hash::check('password-siswa', (string) $siswa->user?->password));
    }

    public function test_it_creates_import_account_from_nisn(): void
    {
        $kelas = $this->createKelas();

        $result = app(SiswaService::class)->upsertFromImport(
            '100000012',
            'Siswa Import',
            $kelas->getKey(),
        );

        $siswa = $result['siswa']->load('user');

        $this->assertSame('created', $result['status']);
        $this->assertSame(Siswa::STATUS_AKTIF, $siswa->status);
        $this->assertSame('100000012', $siswa->user?->username);
        $this->assertSame('siswa.100000012@login.raport.local', $siswa->user?->email);
        $this->assertTrue(Hash::check('100000012', (string) $siswa->user?->password));
    }

    private function createKelas(): Kelas
    {
        return Kelas::query()->create([
            'nama_kelas' => 'Kelas 1A',
            'tingkat' => 1,
        ]);
    }
}

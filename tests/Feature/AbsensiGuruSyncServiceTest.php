<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AbsensiGuruSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AbsensiGuruSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_maps_teacher_gender_when_creating_and_updating_from_absensi(): void
    {
        config()->set('services.absensi.url', 'https://absensi.example.test/api/v1/gurus');
        config()->set('services.absensi.api_key', 'secret-key');

        Http::fake([
            'https://absensi.example.test/api/v1/gurus' => Http::sequence()
                ->push([
                    'status' => 'success',
                    'data' => [[
                        'nama' => 'Guru Sinkron',
                        'username' => 'guru.sinkron',
                        'email' => 'guru.sinkron@example.test',
                        'no_telepon' => '08123456789',
                        'jk' => 'Laki-laki',
                    ]],
                ])
                ->push([
                    'status' => 'success',
                    'data' => [[
                        'nama' => 'Guru Sinkron',
                        'username' => 'guru.sinkron',
                        'email' => 'guru.sinkron@example.test',
                        'no_telepon' => '08123456789',
                        'jk' => 'P',
                    ]],
                ])
                ->push([
                    'status' => 'success',
                    'data' => [[
                        'nama' => 'Guru Sinkron',
                        'username' => 'guru.sinkron',
                        'email' => 'guru.sinkron@example.test',
                        'no_telepon' => '08123456789',
                    ]],
                ]),
        ]);

        $service = app(AbsensiGuruSyncService::class);

        $this->assertSame(['created' => 1, 'updated' => 0], $service->sync());
        $user = User::query()->where('username', 'guru.sinkron')->firstOrFail();
        $this->assertSame('L', $user->guru?->jenis_kelamin);

        $this->assertSame(['created' => 0, 'updated' => 1], $service->sync());
        $this->assertSame('P', $user->guru?->refresh()->jenis_kelamin);

        $this->assertSame(['created' => 0, 'updated' => 1], $service->sync());
        $this->assertSame('P', $user->guru?->refresh()->jenis_kelamin);

        Http::assertSentCount(3);
        Http::assertSent(static fn ($request): bool => $request->hasHeader('X-API-KEY', 'secret-key'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\GuruService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuruServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_teacher_gender(): void
    {
        $guru = app(GuruService::class)->create([
            'name' => 'Guru Perempuan',
            'username' => 'guru.perempuan',
            'email' => 'guru.perempuan@example.test',
            'password' => 'password-guru',
            'jenis_kelamin' => 'P',
        ]);

        $this->assertSame('P', $guru->jenis_kelamin);
        $this->assertSame(User::ROLE_GURU, $guru->user?->role);
    }
}

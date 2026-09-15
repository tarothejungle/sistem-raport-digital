<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateSuperAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_verified_admin_account(): void
    {
        $this->artisan('admin:create-super')
            ->expectsQuestion('Nama lengkap', 'Administrator Utama')
            ->expectsQuestion('Username', 'Admin.Utama')
            ->expectsQuestion('Email', 'ADMIN@example.com')
            ->expectsQuestion('Kata sandi', 'rahasia123')
            ->expectsQuestion('Konfirmasi kata sandi', 'rahasia123')
            ->expectsOutputToContain('Administrator admin.utama berhasil dibuat.')
            ->assertSuccessful();

        $user = User::query()->where('username', 'admin.utama')->firstOrFail();

        $this->assertSame('Administrator Utama', $user->name);
        $this->assertSame('admin@example.com', $user->email);
        $this->assertSame(User::ROLE_ADMIN, $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
        $this->assertNotSame('rahasia123', $user->password);
    }

    public function test_it_rejects_an_existing_username(): void
    {
        User::factory()->create([
            'username' => 'admin',
            'email' => 'existing@example.com',
        ]);

        $this->artisan('admin:create-super')
            ->expectsQuestion('Nama lengkap', 'Administrator Baru')
            ->expectsQuestion('Username', 'admin')
            ->expectsQuestion('Email', 'new@example.com')
            ->expectsQuestion('Kata sandi', 'rahasia123')
            ->expectsQuestion('Konfirmasi kata sandi', 'rahasia123')
            ->expectsOutputToContain('Akun administrator gagal dibuat.')
            ->assertFailed();

        $this->assertDatabaseMissing('user', ['email' => 'new@example.com']);
    }

    public function test_it_rejects_an_existing_email(): void
    {
        User::factory()->create([
            'username' => 'existing-admin',
            'email' => 'admin@example.com',
        ]);

        $this->artisan('admin:create-super')
            ->expectsQuestion('Nama lengkap', 'Administrator Baru')
            ->expectsQuestion('Username', 'new-admin')
            ->expectsQuestion('Email', 'ADMIN@example.com')
            ->expectsQuestion('Kata sandi', 'rahasia123')
            ->expectsQuestion('Konfirmasi kata sandi', 'rahasia123')
            ->expectsOutputToContain('Akun administrator gagal dibuat.')
            ->assertFailed();

        $this->assertDatabaseMissing('user', ['username' => 'new-admin']);
    }

    public function test_it_rejects_a_password_confirmation_mismatch(): void
    {
        $this->artisan('admin:create-super')
            ->expectsQuestion('Nama lengkap', 'Administrator Baru')
            ->expectsQuestion('Username', 'new-admin')
            ->expectsQuestion('Email', 'new@example.com')
            ->expectsQuestion('Kata sandi', 'rahasia123')
            ->expectsQuestion('Konfirmasi kata sandi', 'berbeda123')
            ->expectsOutputToContain('Akun administrator gagal dibuat.')
            ->assertFailed();

        $this->assertDatabaseMissing('user', ['username' => 'new-admin']);
    }
}

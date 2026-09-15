<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response
            ->assertStatus(200)
            ->assertSee('Master Data & Import', false)
            ->assertSee('Penugasan & Input Nilai', false)
            ->assertSee('Kenaikan & Kelulusan', false)
            ->assertSee('Portal Nilai Siswa')
            ->assertSee('Notifikasi & Akun', false);
    }

    public function test_login_page_does_not_show_the_removed_journey_copy(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertDontSee('Masuk untuk melanjutkan perjalanan belajarmu.');
    }
}

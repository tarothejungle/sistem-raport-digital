<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Pages\MaintenanceSettings;
use App\Models\AnnouncementSetting;
use App\Models\MaintenanceSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WebsiteCommunicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_maintenance_blocks_guests_and_non_administrators(): void
    {
        $this->activeMaintenance();

        $this->get('/admin/login')
            ->assertStatus(503)
            ->assertSee('Update Fitur')
            ->assertSee('Masuk Sebagai Administrator');

        $guru = User::factory()->create(['role' => User::ROLE_GURU]);

        $this->actingAs($guru)
            ->get('/admin')
            ->assertStatus(503)
            ->assertSee('Update Fitur');

        $this->assertGuest();
    }

    public function test_administrator_can_bypass_active_maintenance(): void
    {
        $this->activeMaintenance();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(Dashboard::getUrl())
            ->assertOk();
    }

    public function test_maintenance_automatically_ends_after_estimated_time(): void
    {
        $setting = $this->activeMaintenance();
        $setting->update(['ends_at' => now()->subMinute()]);

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Masuk ke Portal');
    }

    public function test_emergency_login_only_accepts_administrator_role(): void
    {
        $this->activeMaintenance();
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'username' => 'guru-maintenance',
            'password' => 'password',
        ]);

        $this->post(route('maintenance.admin.authenticate'), [
            'login' => $guru->username,
            'password' => 'password',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'username' => 'admin-maintenance',
            'password' => 'password',
        ]);

        $this->post(route('maintenance.admin.authenticate'), [
            'login' => $admin->username,
            'password' => 'password',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_maintenance_form_stores_wib_input_as_utc(): void
    {
        config(['app.timezone' => 'UTC']);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        Livewire::actingAs($admin)
            ->test(MaintenanceSettings::class)
            ->fillForm([
                'enabled' => true,
                'title' => 'Update Fitur',
                'message' => 'Website sedang diperbarui.',
                'starts_at' => '2026-09-19 09:00',
                'ends_at' => '2026-09-19 17:00',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $setting = MaintenanceSetting::query()->where('panel_id', 'admin')->firstOrFail();

        $this->assertSame('2026-09-19 02:00:00', $setting->getRawOriginal('starts_at'));
        $this->assertSame('2026-09-19 10:00:00', $setting->getRawOriginal('ends_at'));
    }

    public function test_before_login_announcement_only_renders_on_login_page(): void
    {
        AnnouncementSetting::query()->create([
            'enabled' => true,
            'title' => 'Informasi Login',
            'message' => 'Pesan sebelum login.',
            'placement' => AnnouncementSetting::PLACEMENT_BEFORE_LOGIN,
        ]);

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Informasi Login')
            ->assertSee('Pesan sebelum login.')
            ->assertSee('alwaysShow: true', false);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(Dashboard::getUrl())
            ->assertOk()
            ->assertDontSee('Informasi Login');
    }

    public function test_after_login_announcement_renders_for_authenticated_user(): void
    {
        AnnouncementSetting::query()->create([
            'enabled' => true,
            'title' => 'Informasi Dashboard',
            'message' => 'Pesan setelah login.',
            'placement' => AnnouncementSetting::PLACEMENT_AFTER_LOGIN,
        ]);

        $this->get('/admin/login')
            ->assertOk()
            ->assertDontSee('Informasi Dashboard');

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(Dashboard::getUrl())
            ->assertOk()
            ->assertSee('Informasi Dashboard')
            ->assertSee('Pesan setelah login.');
    }

    private function activeMaintenance(): MaintenanceSetting
    {
        return MaintenanceSetting::query()->updateOrCreate(
            ['panel_id' => 'admin'],
            [
                'enabled' => true,
                'title' => 'Update Fitur',
                'message' => 'Website sedang diperbarui.',
                'starts_at' => now()->subMinute(),
                'ends_at' => now()->addDay(),
            ],
        );
    }
}

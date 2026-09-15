<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Auth\EditProfile;
use App\Filament\Admin\Resources\PengaturanMadrasahResource\Pages\EditPengaturanMadrasah;
use App\Filament\Admin\Widgets\RaportCommandCenter;
use App\Models\PengaturanMadrasah;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Livewire\Topbar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentFileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Storage::fake('public');
    }

    public function test_admin_can_save_madrasah_logo_without_livewire_error(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $settings = PengaturanMadrasah::query()->create([
            'nama_madrasah' => 'Madrasah Uji',
        ]);

        Livewire::actingAs($admin)
            ->test(EditPengaturanMadrasah::class, ['record' => $settings->getRouteKey()])
            ->fillForm([
                'nama_madrasah' => 'Madrasah Uji',
                'logo_path' => UploadedFile::fake()->image('logo.png', 256, 256),
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNoRedirect();

        $logoPath = $settings->refresh()->logo_path;

        $this->assertNotNull($logoPath);
        Storage::disk('public')->assertExists($logoPath);
        $this->get('/storage/'.$logoPath)->assertOk();
    }

    public function test_admin_panel_uses_standard_navigation_for_reverse_proxy_compatibility(): void
    {
        $this->assertFalse(Filament::getPanel('admin')->hasSpaMode());
    }

    public function test_saving_profile_dispatches_latest_avatar_url_for_topbar(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'avatar_path' => null,
        ]);

        $component = Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'avatar_path' => UploadedFile::fake()->image('avatar.png', 256, 256),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $avatarPath = $user->refresh()->avatar_path;

        $this->assertNotNull($avatarPath);
        Storage::disk('public')->assertExists($avatarPath);
        $component->assertDispatched('profile-avatar-updated', url: '/storage/'.$avatarPath);
    }

    public function test_delete_avatar_updates_the_topbar_avatar(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_GURU,
            'avatar_path' => 'avatars/profile.png',
        ]);
        Storage::disk('public')->put($user->avatar_path, 'avatar');

        $component = Livewire::actingAs($user)
            ->test(RaportCommandCenter::class);

        $action = $component->instance()->deleteAvatarAction();

        $this->assertTrue($action->isConfirmationRequired());
        $this->assertSame('Hapus foto profil?', $action->getModalHeading());
        $this->assertSame('raport-delete-avatar-action', $action->getExtraAttributes()['class']);

        $component
            ->callAction('deleteAvatar')
            ->assertDispatched('avatarDeleted')
            ->assertDispatched('profile-avatar-updated');

        $this->assertNull($user->refresh()->avatar_path);
        Storage::disk('public')->assertMissing('avatars/profile.png');
    }

    public function test_logout_uses_a_filament_confirmation_modal(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(Topbar::class)
            ->assertSeeInOrder([
                'Ubah Profil',
                'Ganti Kata Sandi',
                'Keluar',
            ]);

        $action = $component->instance()->getAction('logout');

        $this->assertNotNull($action);
        $this->assertTrue($action->isConfirmationRequired());
        $this->assertSame('Keluar dari akun?', $action->getModalHeading());

        $component
            ->callAction('logout')
            ->assertRedirect(Filament::getLoginUrl());

        $this->assertGuest();
    }
}

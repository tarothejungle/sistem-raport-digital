<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_null_when_no_avatar_is_stored(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_GURU,
            'avatar_path' => null,
        ]);

        $this->assertNull($user->getFilamentAvatarUrl());
    }

    public function test_the_public_disk_url_is_host_relative(): void
    {
        // A hardcoded APP_URL host would point local requests at production.
        $this->assertSame('/storage', config('filesystems.disks.public.url'));
    }

    public function test_public_uploads_are_served_by_the_public_disk_route(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()
            ->image('avatar.png')
            ->store('avatars', 'public');

        $this->assertTrue(Route::has('storage.public'));
        Storage::disk('public')->assertExists($path);

        $this->get('/storage/'.$path)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_uploaded_avatars_for_every_role_resolve_to_servable_urls(): void
    {
        Storage::fake('public');

        foreach ([User::ROLE_ADMIN, User::ROLE_GURU, User::ROLE_SISWA] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'avatar_path' => null,
            ]);

            $path = UploadedFile::fake()
                ->image("foto-{$role}.jpg")
                ->store('avatars', 'public');

            $user->update(['avatar_path' => $path]);

            Storage::disk('public')->assertExists($path);

            $this->assertSame('/storage/'.$path, $user->getFilamentAvatarUrl());
            $this->get($user->getFilamentAvatarUrl())->assertOk();
        }
    }

    public function test_the_avatar_url_survives_a_trailing_slash_in_app_url(): void
    {
        config(['app.url' => 'https://example.test/']);

        $user = User::factory()->create([
            'role' => User::ROLE_GURU,
            'avatar_path' => 'avatars/contoh.jpg',
        ]);

        $this->assertSame(
            '/storage/avatars/contoh.jpg',
            $user->getFilamentAvatarUrl(),
            'The URL must not gain a double slash or absolute host.',
        );
    }
}

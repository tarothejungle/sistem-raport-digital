<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use Zvizvi\FilamentNotificationsTabs\Livewire\DatabaseNotifications;

class NotificationTabsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $panel = Filament::getPanel('admin');

        Filament::setCurrentPanel($panel);
        $panel->getPlugin('filament-notifications-tabs')->boot($panel);
    }

    public function test_notification_tabs_follow_the_project_notification_relation(): void
    {
        $user = User::factory()->create();
        $unreadId = (string) Str::uuid();
        $readId = (string) Str::uuid();

        $user->notifications()->createMany([
            [
                'id' => $unreadId,
                'type' => 'test',
                'data' => ['format' => 'filament', 'title' => 'Belum dibaca'],
                'read_at' => null,
            ],
            [
                'id' => $readId,
                'type' => 'test',
                'data' => ['format' => 'filament', 'title' => 'Sudah dibaca'],
                'read_at' => now(),
            ],
        ]);

        $this->actingAs($user);

        $component = app(DatabaseNotifications::class);
        $component->mount();

        $this->assertSame('unread', $component->tab);
        $this->assertCount(1, $component->getNotifications());
        $this->assertTrue($component->deleteNotificationAction()->isConfirmationRequired());

        $component->setTab('all');
        $this->assertCount(2, $component->getNotifications());

        $component->toggleNotificationReadStatus($unreadId);
        $this->assertNotNull($user->notifications()->findOrFail($unreadId)->read_at);

        $component->deleteNotification($readId);
        $this->assertDatabaseMissing('notification', ['id' => $readId]);
    }
}

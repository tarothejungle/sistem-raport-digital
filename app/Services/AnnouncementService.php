<?php

namespace App\Services;

use App\Models\AnnouncementSetting;
use Illuminate\Support\Facades\Schema;

class AnnouncementService
{
    public function setting(): ?AnnouncementSetting
    {
        if (! Schema::hasTable('announcement_settings')) {
            return null;
        }

        return AnnouncementSetting::query()->first();
    }

    public function activeFor(string $placement): ?AnnouncementSetting
    {
        $setting = $this->setting();

        return $setting?->isActiveFor($placement) ? $setting : null;
    }
}

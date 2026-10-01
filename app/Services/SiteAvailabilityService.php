<?php

namespace App\Services;

use App\Models\MaintenanceSetting;
use Illuminate\Support\Facades\Schema;

class SiteAvailabilityService
{
    public function setting(): ?MaintenanceSetting
    {
        if (! Schema::hasTable('filament_maintenance_settings')) {
            return null;
        }

        return MaintenanceSetting::query()->firstOrCreate(
            ['panel_id' => 'admin'],
            [
                'title' => config('filament-maintenance.default_title'),
                'message' => config('filament-maintenance.default_message'),
            ],
        );
    }

    public function activeSetting(): ?MaintenanceSetting
    {
        $setting = $this->setting();

        return $setting?->isActive() ? $setting : null;
    }
}

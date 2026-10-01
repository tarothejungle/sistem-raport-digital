<?php

namespace App\Models;

use Albertofuentes\FilamentMaintenance\Models\MaintenanceSetting as BaseMaintenanceSetting;

class MaintenanceSetting extends BaseMaintenanceSetting
{
    protected $casts = [
        'enabled' => 'boolean',
        'allowed_ips' => 'array',
        'allowed_roles' => 'array',
        'manager_user_ids' => 'array',
        'manager_roles' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'enabled_at' => 'datetime',
        'disabled_at' => 'datetime',
    ];

    public function isActive(): bool
    {
        return $this->enabled
            && ($this->starts_at === null || $this->starts_at->isPast())
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }
}

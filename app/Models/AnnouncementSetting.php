<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncementSetting extends Model
{
    public const PLACEMENT_BEFORE_LOGIN = 'before_login';

    public const PLACEMENT_AFTER_LOGIN = 'after_login';

    public const PLACEMENT_BOTH = 'both';

    protected $fillable = [
        'enabled',
        'title',
        'message',
        'placement',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function isActiveFor(string $placement): bool
    {
        return $this->enabled
            && in_array($this->placement, [$placement, self::PLACEMENT_BOTH], true)
            && ($this->starts_at === null || $this->starts_at->isPast())
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }
}

<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification as BaseDatabaseNotification;

/**
 * Overrides Laravel's hardcoded `notifications` table with the singular
 * table name used by this project.
 */
class DatabaseNotification extends BaseDatabaseNotification
{
    protected $table = 'notification';
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guru extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'nik',
        'no_telp',
        'can_input_nilai',
    ];
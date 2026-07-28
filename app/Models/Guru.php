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
        // 'nik',
        'no_telp',
        'tempat_lahir',
        'tanggal_lahir',
        'pendidikan_terakhir',
        'can_input_nilai',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'can_input_nilai' => 'boolean',
            'tanggal_lahir' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jadwalMengajars(): HasMany
    {
        return $this->hasMany(JadwalMengajar::class);
    }

    public function getNamaAttribute(): string
    {
        return $this->user?->name ?? '-';
    }
}

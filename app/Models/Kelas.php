<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kelas extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'nama_kelas',
        'tingkat',
        'wali_kelas_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tingkat' => 'integer',
        ];
    }

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'wali_kelas_id');
    }
    public function siswas(): HasMany
    {
        return $this->hasMany(Siswa::class);
    }

    public function jadwalMengajars(): HasMany
    {
        return $this->hasMany(JadwalMengajar::class);
    }
}

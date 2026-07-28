<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MataPelajaran extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'kode_mapel',
        'nama_mapel',
        'kelompok',
    ];

    /**
     * @return array<string, string>
     */
    public function jadwalMengajars(): HasMany
    {
        return $this->hasMany(JadwalMengajar::class, 'mapel_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiSiswa extends Model
{
    protected $table = 'absensi_siswa';

    protected $fillable = [
        'siswa_id',
        'tahun_ajaran_id',
        'sakit',
        'izin',
        'alpa',
    ];

    protected function casts(): array
    {
        return [
            'sakit' => 'integer',
            'izin' => 'integer',
            'alpa' => 'integer',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }
}

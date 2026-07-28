<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KkmPengajar extends Model
{
    protected $fillable = [
        'guru_id',
        'mapel_id',
        'tahun_ajaran_id',
        'kkm',
    ];

    protected function casts(): array
    {
        return [
            'kkm' => 'integer',
        ];
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(
            MataPelajaran::class,
            'mapel_id',
        );
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }
}

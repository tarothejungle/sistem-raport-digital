<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Nilai extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'siswa_id',
        'jadwal_mengajar_id',
        'kkm',
        'nilai_angka',
        'predikat',
        'indeks_ketercapaian',
        'deskripsi',
        'nilai_tugas',
        'nilai_uts',
        'nilai_uas',
        'nilai_akhir',
        'is_submitted',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kkm' => 'integer',
            'nilai_angka' => 'integer',
            'nilai_tugas' => 'integer',
            'nilai_uts' => 'integer',
            'nilai_uas' => 'integer',
            'nilai_akhir' => 'integer',
            'is_submitted' => 'boolean',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function jadwalMengajar(): BelongsTo
    {
        return $this->belongsTo(JadwalMengajar::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatKelasSiswa extends Model
{
    protected $table = 'riwayat_kelas_siswa';

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_LULUS = 'lulus';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'siswa_id',
        'tahun_ajaran_id',
        'kelas_id',
        'status',
        'diproses_pada',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'diproses_pada' => 'datetime',
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

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }
}

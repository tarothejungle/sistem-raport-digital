<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Siswa extends Model
{
    protected $table = 'siswa';

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_ALUMNI = 'alumni';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'nisn',
        'nama_lengkap',
        'kelas_id',
        'can_view_nilai',
        'status',
        'tahun_lulus_id',
        'tanggal_lulus',
        'keterangan_alumni',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'can_view_nilai' => 'boolean',
            'tanggal_lulus' => 'date',
        ];
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_AKTIF);
    }

    public function scopeAlumni(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ALUMNI);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function tahunLulus(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_lulus_id');
    }

    public function nilais(): HasMany
    {
        return $this->hasMany(Nilai::class);
    }

    public function catatanRapors(): HasMany
    {
        return $this->hasMany(CatatanRapor::class);
    }

    public function riwayatKelasSiswas(): HasMany
    {
        return $this->hasMany(RiwayatKelasSiswa::class);
    }

    public function isAlumni(): bool
    {
        return $this->status === self::STATUS_ALUMNI;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahunAjaran extends Model
{
    protected $table = 'tahun_ajaran';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'nama',
        'semester',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (self $tahunAjaran): void {
            if (! $tahunAjaran->is_active) {
                return;
            }

            static::query()
                ->whereKeyNot($tahunAjaran->getKey())
                ->where('is_active', true)
                ->update(['is_active' => false]);
        });
    }

    public function jadwalMengajars(): HasMany
    {
        return $this->hasMany(JadwalMengajar::class);
    }

    public function riwayatKelasSiswas(): HasMany
    {
        return $this->hasMany(RiwayatKelasSiswa::class);
    }

    public function alumniSiswas(): HasMany
    {
        return $this->hasMany(Siswa::class, 'tahun_lulus_id');
    }

    public function catatanRapors(): HasMany
    {
        return $this->hasMany(CatatanRapor::class);
    }

    public function absensiSiswas(): HasMany
    {
        return $this->hasMany(AbsensiSiswa::class);
    }

    public function getLabelAttribute(): string
    {
        return sprintf('%s - %s', $this->nama, $this->semester);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class JadwalMengajar extends Model
{
    protected $table = 'jadwal_mengajar';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'guru_id',
        'mapel_id',
        'kelas_id',
        'tahun_ajaran_id',
    ];

    protected static function booted(): void
    {
        static::created(static function (self $jadwalMengajar): void {
            static::syncGuruInputPermission((int) $jadwalMengajar->guru_id);
        });

        static::updated(static function (self $jadwalMengajar): void {
            static::syncGuruInputPermission((int) $jadwalMengajar->guru_id);

            if ($jadwalMengajar->wasChanged('guru_id')) {
                static::syncGuruInputPermission((int) $jadwalMengajar->getOriginal('guru_id'));
            }
        });

        static::deleted(static function (self $jadwalMengajar): void {
            static::syncGuruInputPermission((int) $jadwalMengajar->guru_id);
        });
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'mapel_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function nilais(): HasMany
    {
        return $this->hasMany(Nilai::class);
    }

    public function getLabelAttribute(): string
    {
        $this->loadMissing(['guru.user', 'mataPelajaran', 'kelas', 'tahunAjaran']);

        return collect([
            $this->mataPelajaran?->nama_mapel,
            $this->kelas?->nama_kelas,
            $this->guru?->user?->name,
            $this->tahunAjaran?->label,
        ])->filter()->implode(' - ');
    }

    public function getSlug(): string
    {
        $this->loadMissing(['mataPelajaran', 'kelas']);

        $mapel = Str::slug($this->mataPelajaran?->nama_mapel ?? 'mapel');
        $kelas = Str::slug($this->kelas?->nama_kelas ?? 'kelas');

        return "{$mapel}-{$kelas}";
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function resolveRouteBinding($value, $field = null): ?static
    {
        $id = (int) preg_replace('/^.*-(\d+)$/', '$1', (string) $value);

        return $id > 0 ? static::find($id) : null;
    }

    private static function syncGuruInputPermission(int $guruId): void
    {
        if ($guruId <= 0) {
            return;
        }

        Guru::query()
            ->whereKey($guruId)
            ->update([
                'can_input_nilai' => static::query()
                    ->where('guru_id', $guruId)
                    ->exists(),
            ]);
    }
}

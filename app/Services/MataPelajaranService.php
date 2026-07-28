<?php

namespace App\Services;

use App\Models\MataPelajaran;

class MataPelajaranService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MataPelajaran
    {
        return MataPelajaran::query()->create([
            'kode_mapel' => (int) $data['kode_mapel'],
            'nama_mapel' => $data['nama_mapel'],
            'kelompok' => $data['kelompok'],
        ]);
    }
}

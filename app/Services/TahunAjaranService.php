<?php

namespace App\Services;

use App\Models\TahunAjaran;

class TahunAjaranService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TahunAjaran
    {
        return TahunAjaran::query()->create([
            'nama' => $data['nama'],
            'semester' => $data['semester'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);
    }
}

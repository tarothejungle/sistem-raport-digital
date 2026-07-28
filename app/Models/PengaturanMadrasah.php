<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengaturanMadrasah extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'nama_madrasah',
        'logo_path',
        'nama_kepala_madrasah',
        'nip_kepala_madrasah',
        'kota',
        'ttd_kepala_path',
    ];
}

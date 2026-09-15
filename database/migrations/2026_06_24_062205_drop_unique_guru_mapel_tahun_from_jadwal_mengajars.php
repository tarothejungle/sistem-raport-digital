<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal_mengajar', function (Blueprint $table): void {
            $table->dropUnique('jadwal_mengajar_unique_guru_mapel_tahun');
        });
    }

    public function down(): void
    {
        Schema::table('jadwal_mengajar', function (Blueprint $table): void {
            $table->unique(
                ['guru_id', 'mapel_id', 'tahun_ajaran_id'],
                'jadwal_mengajar_unique_guru_mapel_tahun',
            );
        });
    }
};

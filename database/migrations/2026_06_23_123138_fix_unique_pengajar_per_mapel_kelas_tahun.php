<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal_mengajars', function (Blueprint $table): void {
            /*
            * Index tunggal ini wajib dibuat lebih dulu karena foreign key guru_id
            * sebelumnya memakai index unique lama sebagai penopang relasi.
            */
            $table->index('guru_id', 'jadwal_mengajar_guru_id_index');

            $table->dropUnique('jadwal_mengajar_unique_assignment');

            // Satu mapel hanya boleh memiliki satu guru pada satu kelas
            // dan satu tahun ajaran.
            $table->unique(
                ['mapel_id', 'kelas_id', 'tahun_ajaran_id'],
                'jadwal_mengajar_unique_mapel_kelas_tahun',
            );

            // Satu guru tidak boleh mengajar mapel yang sama
            // pada kelas lain di tahun ajaran yang sama.
            $table->unique(
                ['guru_id', 'mapel_id', 'tahun_ajaran_id'],
                'jadwal_mengajar_unique_guru_mapel_tahun',
            );
        });
    }

    public function down(): void
    {
        Schema::table('jadwal_mengajars', function (Blueprint $table): void {
            $table->dropUnique('jadwal_mengajar_unique_mapel_kelas_tahun');
            $table->dropUnique('jadwal_mengajar_unique_guru_mapel_tahun');

            $table->unique(
                ['guru_id', 'mapel_id', 'kelas_id', 'tahun_ajaran_id'],
                'jadwal_mengajar_unique_assignment',
            );

            $table->dropIndex('jadwal_mengajar_guru_id_index');
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('guru', function (Blueprint $table): void {
            $table->unique('user_id');
        });

        Schema::table('kelas', function (Blueprint $table): void {
            $table->unique('nama_kelas');
        });

        Schema::table('tahun_ajaran', function (Blueprint $table): void {
            $table->unique(['nama', 'semester']);
        });

        Schema::table('jadwal_mengajar', function (Blueprint $table): void {
            $table->unique(['guru_id', 'mapel_id', 'kelas_id', 'tahun_ajaran_id'], 'jadwal_mengajar_unique_assignment');
        });

        Schema::table('nilai', function (Blueprint $table): void {
            $table->unique(['siswa_id', 'jadwal_mengajar_id'], 'nilai_unique_siswa_jadwal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nilai', function (Blueprint $table): void {
            $table->dropUnique('nilai_unique_siswa_jadwal');
        });

        Schema::table('jadwal_mengajar', function (Blueprint $table): void {
            $table->dropUnique('jadwal_mengajar_unique_assignment');
        });

        Schema::table('tahun_ajaran', function (Blueprint $table): void {
            $table->dropUnique(['nama', 'semester']);
        });

        Schema::table('kelas', function (Blueprint $table): void {
            $table->dropUnique(['nama_kelas']);
        });

        Schema::table('guru', function (Blueprint $table): void {
            $table->dropUnique(['user_id']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table): void {
            $table->string('status', 20)->default('aktif')->index();
            $table->foreignId('tahun_lulus_id')
                ->nullable()
                ->constrained('tahun_ajaran')
                ->nullOnDelete();
            $table->date('tanggal_lulus')->nullable();
            $table->string('keterangan_alumni')->nullable();
        });

        Schema::create('riwayat_kelas_siswa', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('siswa_id')
                ->constrained('siswa')
                ->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')
                ->constrained('tahun_ajaran')
                ->cascadeOnDelete();
            $table->foreignId('kelas_id')
                ->constrained('kelas')
                ->cascadeOnDelete();
            $table->string('status', 20)->default('aktif');
            $table->timestamp('diproses_pada')->nullable();
            $table->timestamps();

            $table->unique(['siswa_id', 'tahun_ajaran_id'], 'riwayat_kelas_siswa_per_tahun_unique');
            $table->index(['tahun_ajaran_id', 'kelas_id'], 'riwayat_kelas_tahun_kelas_index');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_kelas_siswa');

        Schema::table('siswa', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tahun_lulus_id');
            $table->dropColumn([
                'status',
                'tanggal_lulus',
                'keterangan_alumni',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table): void {
            $table->enum('jenis_kelamin', ['L', 'P'])
                ->nullable()
                ->after('nama_lengkap');
        });

        Schema::table('guru', function (Blueprint $table): void {
            $table->enum('jenis_kelamin', ['L', 'P'])
                ->nullable()
                ->after('user_id');
        });

        Schema::create('absensi_siswa', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('siswa_id')
                ->constrained('siswa')
                ->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')
                ->constrained('tahun_ajaran')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('sakit')->default(0);
            $table->unsignedSmallInteger('izin')->default(0);
            $table->unsignedSmallInteger('alpa')->default(0);
            $table->timestamps();
            $table->unique(['siswa_id', 'tahun_ajaran_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_siswa');

        Schema::table('guru', function (Blueprint $table): void {
            $table->dropColumn('jenis_kelamin');
        });

        Schema::table('siswa', function (Blueprint $table): void {
            $table->dropColumn('jenis_kelamin');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catatan_rapor', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('siswa_id')
                ->constrained('siswa')
                ->cascadeOnDelete();

            $table->foreignId('tahun_ajaran_id')
                ->constrained('tahun_ajaran')
                ->cascadeOnDelete();

            $table->text('saran')->nullable();

            $table->timestamps();

            $table->unique(
                ['siswa_id', 'tahun_ajaran_id'],
                'catatan_rapor_unique_siswa_tahun',
            );
        });

        Schema::table('siswa', function (Blueprint $table): void {
            $table->dropColumn('saran_rapor');
        });
    }

    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table): void {
            $table->text('saran_rapor')
                ->nullable()
                ->after('can_view_nilai');
        });

        Schema::dropIfExists('catatan_rapor');
    }
};

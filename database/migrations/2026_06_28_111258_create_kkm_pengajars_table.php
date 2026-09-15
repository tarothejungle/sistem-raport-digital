<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kkm_pengajar', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('guru_id')
                ->constrained('guru')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('mapel_id')
                ->constrained('mata_pelajaran')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('tahun_ajaran_id')
                ->constrained('tahun_ajaran')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('kkm');

            $table->timestamps();

            $table->unique(
                ['guru_id', 'mapel_id', 'tahun_ajaran_id'],
                'kkm_pengajar_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kkm_pengajar');
    }
};

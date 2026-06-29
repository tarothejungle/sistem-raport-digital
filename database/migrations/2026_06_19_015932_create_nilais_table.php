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
        Schema::create('nilais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('jadwal_mengajar_id')->constrained('jadwal_mengajars')->cascadeOnDelete();
            $table->integer('nilai_tugas')->nullable();
            $table->integer('nilai_uts')->nullable();
            $table->integer('nilai_uas')->nullable();
            $table->integer('nilai_akhir')->nullable();
            $table->boolean('is_submitted')->default(false); // Flag Bukti Selesai
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nilais');
    }
};

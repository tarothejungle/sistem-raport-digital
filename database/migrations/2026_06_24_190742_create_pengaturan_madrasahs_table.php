<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan_madrasahs', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_madrasah')->default('Sistem Raport Digital');
            $table->string('logo_path')->nullable();
            $table->string('nama_kepala_madrasah')->nullable();
            $table->string('nip_kepala_madrasah')->nullable();
            $table->string('kota')->nullable();
            $table->string('ttd_kepala_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_madrasahs');
    }
};

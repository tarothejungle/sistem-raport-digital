<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gurus', function (Blueprint $table): void {
            $table->string('tempat_lahir', 100)->nullable()->after('no_telp');
            $table->date('tanggal_lahir')->nullable()->after('tempat_lahir');
            $table->string('pendidikan_terakhir', 100)->nullable()->after('tanggal_lahir');
        });
    }

    public function down(): void
    {
        Schema::table('gurus', function (Blueprint $table): void {
            $table->dropColumn([
                'tempat_lahir',
                'tanggal_lahir',
                'pendidikan_terakhir',
            ]);
        });
    }
};

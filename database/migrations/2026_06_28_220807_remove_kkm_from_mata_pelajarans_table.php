<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mata_pelajarans', function (Blueprint $table): void {
            $table->dropColumn('kkm');
        });
    }

    public function down(): void
    {
        Schema::table('mata_pelajarans', function (Blueprint $table): void {
            $table->unsignedTinyInteger('kkm')
                ->default(70)
                ->after('kelompok');
        });
    }
};
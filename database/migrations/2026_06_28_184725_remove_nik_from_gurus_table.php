<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gurus', function (Blueprint $table): void {
            $table->dropUnique(['nik']);
            $table->dropColumn('nik');
        });
    }

    public function down(): void
    {
        Schema::table('gurus', function (Blueprint $table): void {
            $table->string('nik', 16)
                ->nullable()
                ->unique();
        });
    }
};
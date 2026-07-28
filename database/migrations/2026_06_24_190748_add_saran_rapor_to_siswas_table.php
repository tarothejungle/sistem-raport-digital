<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswas', function (Blueprint $table): void {
            $table->text('saran_rapor')
                ->nullable()
                ->after('can_view_nilai');
        });
    }

    public function down(): void
    {
        Schema::table('siswas', function (Blueprint $table): void {
            $table->dropColumn('saran_rapor');
        });
    }
};

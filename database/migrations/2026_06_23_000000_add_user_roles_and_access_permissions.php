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
        Schema::table('user', function (Blueprint $table): void {
            $table->string('role', 20)->default('guru')->change();
        });

        Schema::table('guru', function (Blueprint $table): void {
            $table->boolean('can_input_nilai')->default(false)->after('no_telp');
        });

        Schema::table('siswa', function (Blueprint $table): void {
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->after('id')
                ->constrained('user')
                ->nullOnDelete();
            $table->boolean('can_view_nilai')->default(false)->after('kelas_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table): void {
            $table->dropColumn('can_view_nilai');
            $table->dropUnique(['user_id']);
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('guru', function (Blueprint $table): void {
            $table->dropColumn('can_input_nilai');
        });
    }
};

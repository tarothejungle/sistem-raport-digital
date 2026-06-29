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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 20)->default('guru')->change();
        });

        Schema::table('gurus', function (Blueprint $table): void {
            $table->boolean('can_input_nilai')->default(false)->after('no_telp');
        });

        Schema::table('siswas', function (Blueprint $table): void {
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
            $table->boolean('can_view_nilai')->default(false)->after('kelas_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('siswas', function (Blueprint $table): void {
            $table->dropColumn('can_view_nilai');
            $table->dropUnique(['user_id']);
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('gurus', function (Blueprint $table): void {
            $table->dropColumn('can_input_nilai');
        });
    }
};

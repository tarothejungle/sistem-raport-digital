<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gurus', function (Blueprint $table): void {
            $table->string('nik', 16)->nullable()->unique()->after('user_id');
        });

        DB::table('gurus')
            ->select(['id', 'nip'])
            ->orderBy('id')
            ->cursor()
            ->each(static function (object $guru): void {
                $legacyNip = preg_replace('/\D+/', '', (string) $guru->nip) ?? '';

                if (preg_match('/^\d{16}$/', $legacyNip)) {
                    DB::table('gurus')
                        ->where('id', $guru->id)
                        ->update(['nik' => $legacyNip]);
                }
            });

        Schema::table('gurus', function (Blueprint $table): void {
            $table->dropUnique(['nip']);
            $table->dropColumn('nip');
        });

        DB::table('gurus')->update(['can_input_nilai' => false]);

        DB::table('jadwal_mengajars')
            ->select('guru_id')
            ->distinct()
            ->orderBy('guru_id')
            ->cursor()
            ->each(static function (object $jadwal): void {
                DB::table('gurus')
                    ->where('id', $jadwal->guru_id)
                    ->update(['can_input_nilai' => true]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gurus', function (Blueprint $table): void {
            $table->string('nip', 30)->nullable()->unique()->after('user_id');
        });

        DB::table('gurus')
            ->select(['id', 'nik'])
            ->orderBy('id')
            ->cursor()
            ->each(static function (object $guru): void {
                DB::table('gurus')
                    ->where('id', $guru->id)
                    ->update(['nip' => $guru->nik]);
            });

        Schema::table('gurus', function (Blueprint $table): void {
            $table->dropUnique(['nik']);
            $table->dropColumn('nik');
        });
    }
};

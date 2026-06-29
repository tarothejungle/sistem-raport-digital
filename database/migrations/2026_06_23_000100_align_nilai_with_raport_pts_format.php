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
        Schema::table('mata_pelajarans', function (Blueprint $table): void {
            $table->enum('kelompok', ['A', 'B'])->default('A')->after('nama_mapel');
            $table->integer('kkm')->default(70)->change();
        });

        Schema::table('nilais', function (Blueprint $table): void {
            $table->unsignedTinyInteger('kkm')->nullable()->after('jadwal_mengajar_id');
            $table->unsignedTinyInteger('nilai_angka')->nullable()->after('kkm');
            $table->char('predikat', 1)->nullable()->after('nilai_angka');
            $table->text('deskripsi')->nullable()->after('predikat');
        });

        DB::table('nilais')
            ->join('jadwal_mengajars', 'jadwal_mengajars.id', '=', 'nilais.jadwal_mengajar_id')
            ->join('mata_pelajarans', 'mata_pelajarans.id', '=', 'jadwal_mengajars.mapel_id')
            ->select([
                'nilais.id',
                'nilais.nilai_akhir',
                'mata_pelajarans.kkm as mapel_kkm',
            ])
            ->orderBy('nilais.id')
            ->cursor()
            ->each(function (object $nilai): void {
                $kkm = (int) ($nilai->mapel_kkm ?? 70);
                $nilaiAngka = is_numeric($nilai->nilai_akhir) ? (int) $nilai->nilai_akhir : null;

                $predikat = $nilaiAngka === null
                    ? null
                    : match (true) {
                        $nilaiAngka >= 90 => 'A',
                        $nilaiAngka >= 80 => 'B',
                        $nilaiAngka >= $kkm => 'C',
                        default => 'D',
                    };

                DB::table('nilais')
                    ->where('id', $nilai->id)
                    ->update([
                        'kkm' => $kkm,
                        'nilai_angka' => $nilaiAngka,
                        'predikat' => $predikat,
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nilais', function (Blueprint $table): void {
            $table->dropColumn(['kkm', 'nilai_angka', 'predikat', 'deskripsi']);
        });

        Schema::table('mata_pelajarans', function (Blueprint $table): void {
            $table->dropColumn('kelompok');
            $table->integer('kkm')->default(75)->change();
        });
    }
};

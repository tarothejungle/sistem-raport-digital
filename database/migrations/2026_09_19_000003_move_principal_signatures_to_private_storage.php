<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pengaturan_madrasah')) {
            return;
        }

        DB::table('pengaturan_madrasah')
            ->whereNotNull('ttd_kepala_path')
            ->pluck('ttd_kepala_path')
            ->filter()
            ->each(function (string $path): void {
                if (! Storage::disk('public')->exists($path)) {
                    return;
                }

                if (! Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->put($path, Storage::disk('public')->get($path));
                }

                Storage::disk('public')->delete($path);
            });
    }

    public function down(): void
    {
        // Sensitive signatures remain private after rollback.
    }
};

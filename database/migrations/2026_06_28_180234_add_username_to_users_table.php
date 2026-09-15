<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user', function (Blueprint $table): void {
            $table->string('username', 50)
                ->nullable()
                ->after('name');
        });

        DB::table('user')
            ->orderBy('id')
            ->get()
            ->each(function (object $user): void {
                $candidate = match ($user->role) {
                    'guru' => DB::table('guru')
                        ->where('user_id', $user->id)
                        ->value('nik'),

                    'siswa' => DB::table('siswa')
                        ->where('user_id', $user->id)
                        ->value('nisn'),

                    default => explode('@', (string) $user->email)[0] ?? '',
                };

                DB::table('user')
                    ->where('id', $user->id)
                    ->update([
                        'username' => $this->makeUniqueUsername(
                            (string) $candidate,
                            (int) $user->id,
                        ),
                    ]);
            });

        Schema::table('user', function (Blueprint $table): void {
            $table->string('username', 50)
                ->nullable(false)
                ->change();

            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('user', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }

    private function makeUniqueUsername(
        string $candidate,
        int $userId,
    ): string {
        $base = strtolower(trim($candidate));

        $base = preg_replace(
            '/[^a-z0-9._-]+/',
            '-',
            $base,
        ) ?? '';

        $base = trim($base, '.-_');
        $base = substr($base, 0, 40);

        if ($base === '') {
            $base = "user-{$userId}";
        }

        $username = $base;
        $counter = 2;

        while (
            DB::table('user')
                ->where('username', $username)
                ->exists()
        ) {
            $suffix = "-{$counter}";

            $username = substr(
                $base,
                0,
                50 - strlen($suffix),
            ).$suffix;

            $counter++;
        }

        return $username;
    }
};

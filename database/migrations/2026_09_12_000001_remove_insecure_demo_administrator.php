<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('user')
            ->where('username', 'demo.admin')
            ->where('email', 'demo.admin@raport.test')
            ->delete();
    }

    public function down(): void
    {
        // Known credentials must never be restored.
    }
};

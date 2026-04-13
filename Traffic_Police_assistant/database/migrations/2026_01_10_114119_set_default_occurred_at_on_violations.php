<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE violations MODIFY occurred_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE violations MODIFY occurred_at TIMESTAMP NULL DEFAULT NULL");
    }
};

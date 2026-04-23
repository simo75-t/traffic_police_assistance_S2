<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('violations', function (Blueprint $table): void {
            if (! Schema::hasColumn('violations', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('violations', function (Blueprint $table): void {
            if (Schema::hasColumn('violations', 'pdf_path')) {
                $table->dropColumn('pdf_path');
            }
        });
    }
};

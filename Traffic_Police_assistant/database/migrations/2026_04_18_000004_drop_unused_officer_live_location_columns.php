<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('officers_live_locations', function (Blueprint $table): void {
            if (Schema::hasColumn('officers_live_locations', 'device_id')) {
                $table->dropColumn('device_id');
            }

            if (Schema::hasColumn('officers_live_locations', 'battery_level')) {
                $table->dropColumn('battery_level');
            }
        });
    }

    public function down(): void
    {
        Schema::table('officers_live_locations', function (Blueprint $table): void {
            if (! Schema::hasColumn('officers_live_locations', 'device_id')) {
                $table->string('device_id')->nullable()->after('last_update_time');
            }

            if (! Schema::hasColumn('officers_live_locations', 'battery_level')) {
                $table->unsignedTinyInteger('battery_level')->nullable()->after('device_id');
            }
        });
    }
};

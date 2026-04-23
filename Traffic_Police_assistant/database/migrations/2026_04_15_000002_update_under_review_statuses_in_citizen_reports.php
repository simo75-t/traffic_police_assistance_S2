<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('citizen_reports')
            ->where('status', 'under_review')
            ->update(['status' => 'in_progress']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('citizen_reports')
            ->where('status', 'in_progress')
            ->update(['status' => 'under_review']);
    }
};

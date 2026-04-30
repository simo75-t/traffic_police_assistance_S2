<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heatmap_predictions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('request_id')->unique();
            $table->uuid('correlation_id')->nullable()->unique();
            $table->string('status', 20)->default('pending');
            $table->string('source')->nullable();
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heatmap_predictions');
    }
};

<?php

namespace Tests\Feature;

use App\Models\HeatmapPrediction;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HeatmapPredictionStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('heatmap_predictions');
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

    public function test_prediction_status_returns_processing_while_job_is_running(): void
    {
        $prediction = HeatmapPrediction::query()->create([
            'request_id' => 'pred-001',
            'correlation_id' => 'corr-001',
            'status' => 'processing',
            'source' => null,
            'payload' => [
                'heatmap_summary' => ['city' => 'Damascus'],
            ],
            'result' => null,
            'error_message' => null,
            'started_at' => now(),
        ]);

        $response = $this->getJson("/api/heatmap-predictions/{$prediction->request_id}");

        $response
            ->assertOk()
            ->assertJson([
                'request_id' => 'pred-001',
                'status' => 'processing',
                'source' => null,
                'data' => null,
            ]);
    }

    public function test_prediction_status_returns_done_with_qwen_result(): void
    {
        $prediction = HeatmapPrediction::query()->create([
            'request_id' => 'pred-002',
            'correlation_id' => 'corr-002',
            'status' => 'done',
            'source' => 'qwen_api',
            'payload' => [
                'heatmap_summary' => ['city' => 'Damascus'],
            ],
            'result' => [
                'request_id' => 'pred-002',
                'city' => 'Damascus',
                'source' => 'qwen_api',
                'prediction_summary' => 'summary',
                'overall_risk_level' => 'high',
                'predicted_hotspots' => [],
                'recommendations' => [],
                'limitations' => [],
            ],
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $response = $this->getJson("/api/heatmap-predictions/{$prediction->request_id}");

        $response
            ->assertOk()
            ->assertJson([
                'request_id' => 'pred-002',
                'status' => 'done',
                'source' => 'qwen_api',
                'data' => [
                    'prediction_summary' => 'summary',
                    'overall_risk_level' => 'high',
                ],
            ]);
    }

    public function test_prediction_status_returns_failed_with_fallback_data(): void
    {
        $prediction = HeatmapPrediction::query()->create([
            'request_id' => 'pred-003',
            'correlation_id' => 'corr-003',
            'status' => 'failed',
            'source' => 'fallback_after_qwen_failure',
            'payload' => [
                'heatmap_summary' => ['city' => 'Damascus'],
            ],
            'result' => [
                'request_id' => 'pred-003',
                'city' => 'Damascus',
                'source' => 'fallback_after_qwen_failure',
                'prediction_summary' => 'fallback summary',
                'overall_risk_level' => 'medium',
                'predicted_hotspots' => [],
                'recommendations' => [],
                'limitations' => ['fallback used'],
            ],
            'error_message' => 'Qwen timeout',
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $response = $this->getJson("/api/heatmap-predictions/{$prediction->request_id}");

        $response
            ->assertOk()
            ->assertJson([
                'request_id' => 'pred-003',
                'status' => 'failed',
                'source' => 'fallback_after_qwen_failure',
                'error_message' => 'Qwen timeout',
                'data' => [
                    'prediction_summary' => 'fallback summary',
                    'overall_risk_level' => 'medium',
                ],
            ]);
    }
}

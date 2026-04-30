<?php

namespace Tests\Feature;

use App\Consumers\AiResultConsumer;
use App\Models\AiJob;
use App\Models\HeatmapPrediction;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AiResultConsumerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('ai_jobs');
        Schema::create('ai_jobs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('job_id')->unique();
            $table->uuid('correlation_id')->nullable()->unique();
            $table->string('type');
            $table->string('status')->default('queued');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('violation_draft_id')->nullable();
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->json('error')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

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

    public function test_consumer_updates_regular_ai_job_result(): void
    {
        $job = AiJob::query()->create([
            'job_id' => 'job-001',
            'correlation_id' => 'corr-001',
            'type' => 'generate_heatmap',
            'status' => 'queued',
            'payload' => ['city' => 'Damascus'],
        ]);

        app(AiResultConsumer::class)->consume(json_encode([
            'job_id' => $job->job_id,
            'status' => 'success',
            'result' => ['city' => 'Damascus'],
            'error' => null,
            'type' => 'generate_heatmap',
        ], JSON_THROW_ON_ERROR));

        $job->refresh();

        $this->assertSame('success', $job->status);
        $this->assertSame(['city' => 'Damascus'], $job->result);
        $this->assertNull($job->error);
        $this->assertNotNull($job->finished_at);
    }

    public function test_consumer_updates_prediction_status_with_fallback_result(): void
    {
        $prediction = HeatmapPrediction::query()->create([
            'request_id' => 'pred-900',
            'correlation_id' => 'corr-900',
            'status' => 'processing',
            'payload' => ['heatmap_summary' => ['city' => 'Damascus']],
            'started_at' => now(),
        ]);

        app(AiResultConsumer::class)->consume(json_encode([
            'job_id' => $prediction->request_id,
            'request_id' => $prediction->request_id,
            'status' => 'success',
            'type' => 'generate_heatmap_prediction',
            'result' => [
                'source' => 'fallback_after_qwen_failure',
                'prediction_summary' => 'fallback summary',
                'overall_risk_level' => 'medium',
                'predicted_hotspots' => [],
                'recommendations' => [],
                'limitations' => [],
            ],
            'error' => 'Qwen timeout',
        ], JSON_THROW_ON_ERROR));

        $prediction->refresh();

        $this->assertSame('failed', $prediction->status);
        $this->assertSame('fallback_after_qwen_failure', $prediction->source);
        $this->assertSame('Qwen timeout', $prediction->error_message);
        $this->assertNotNull($prediction->completed_at);
    }
}

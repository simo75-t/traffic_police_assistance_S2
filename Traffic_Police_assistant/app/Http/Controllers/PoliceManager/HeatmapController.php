<?php

namespace App\Http\Controllers\PoliceManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\PoliceManager\GenerateHeatmapRequest;
use App\Http\Requests\PoliceManager\GenerateHeatmapPredictionRequest;
use App\Http\Resources\PoliceManager\HeatmapPredictionResultResource;
use App\Http\Resources\PoliceManager\HeatmapResultResource;
use App\Http\Services\PoliceManager\HeatmapPredictionService;
use App\Http\Services\PoliceManager\HeatmapService;
use App\Logging\HeatmapPredictionLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HeatmapController extends Controller
{
    public function __construct(
        private readonly HeatmapService $heatmapService,
        private readonly HeatmapPredictionService $heatmapPredictionService,
        private readonly HeatmapPredictionLogger $predictionLogger,
    ) {
    }

    public function generate(GenerateHeatmapRequest $request): JsonResponse
    {
        $validated = $request->validated();
        if ($request->hasMissingComparisonModeForTrend()) {
            return response()->json([
                'status_code' => 422,
                'message' => 'Validation failed',
                'errors' => [
                    'comparison_mode' => ['comparison_mode is required when include_trend is true.'],
                ],
            ], 422);
        }

        $job = $this->heatmapService->queueJob($validated, $request->user()?->id);

        return response()->json([
            'status' => 'queued',
            'job_id' => $job->job_id,
        ]);
    }

    public function result(Request $request, string $job_id): JsonResponse
    {
        $job = $this->heatmapService->findJob($job_id);

        return response()->json((new HeatmapResultResource($job))->resolve());
    }

    public function generatePrediction(GenerateHeatmapPredictionRequest $request): JsonResponse
    {
        try {
            $prediction = $this->heatmapPredictionService->queueJob($request->validated(), $request->user()?->id);
        } catch (\Throwable $exception) {
            $this->predictionLogger->requestFailedBeforeQueuePublish($exception->getMessage());

            return response()->json([
                'status' => 'failed',
                'message' => 'تعذر إرسال طلب التوقعات حالياً. يرجى المحاولة مجدداً.',
            ], 500);
        }

        return response()->json([
            'status' => 'processing',
            'message' => 'AI prediction is being generated',
            'request_id' => $prediction->request_id ?? $prediction->job_id,
            'job_id' => $prediction->request_id ?? $prediction->job_id,
        ]);
    }

    public function predictionResult(Request $request, string $job_id): JsonResponse
    {
        $prediction = $this->heatmapPredictionService->findPrediction($job_id);

        return response()->json((new HeatmapPredictionResultResource($prediction))->resolve());
    }

    public function predictionStatus(Request $request, string $request_id): JsonResponse
    {
        $prediction = $this->heatmapPredictionService->findPrediction($request_id);

        return response()->json((new HeatmapPredictionResultResource($prediction))->resolve());
    }
}

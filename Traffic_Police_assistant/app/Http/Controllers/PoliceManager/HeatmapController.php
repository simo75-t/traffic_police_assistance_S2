<?php

namespace App\Http\Controllers\PoliceManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\PoliceManager\GenerateHeatmapRequest;
use App\Http\Resources\PoliceManager\HeatmapResultResource;
use App\Http\Services\PoliceManager\HeatmapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HeatmapController extends Controller
{
    public function __construct(
        private readonly HeatmapService $heatmapService,
    ) {
    }

    public function generate(GenerateHeatmapRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $includeTrend = (bool) ($validated['include_trend'] ?? false);
        $comparisonMode = $includeTrend
            ? (string) ($validated['comparison_mode'] ?? '')
            : '';

        if ($includeTrend && $comparisonMode === '') {
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
}

<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\CreateCitizenReportRequest;
use App\Http\Resources\CitizenReportResource;
use App\Http\Services\Citizen\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    ) {
    }

    public function store(CreateCitizenReportRequest $request): JsonResponse
    {
        $report = $this->reportService->createReport($request->validated());

        return response()->json([
            'status_code' => 201,
            'message' => 'Report submitted successfully',
            'data' => new CitizenReportResource($report),
        ], 201);
    }
}

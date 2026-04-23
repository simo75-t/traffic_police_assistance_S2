<?php

namespace App\Http\Controllers\PoliceManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\PoliceManager\ViolationHeatmapRequest;
use App\Http\Requests\PoliceManager\ViolationIndexRequest;
use App\Http\Resources\PoliceManager\ViolationListResource;
use App\Http\Services\PoliceManager\ViolationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ViolationControllerPolice extends Controller
{
    public function __construct(private readonly ViolationService $violationService)
    {
    }

    /**
     * Show violations and apply the requested search filter when present.
     */
    public function index(ViolationIndexRequest $request): View
    {
        $validated = $request->validated();
        $searchType = (string) ($validated['search_type'] ?? '');
        $searchValue = trim((string) ($validated['search'] ?? ''));
        $violations = $this->violationService->getFilteredViolations($validated);
        $violationRows = ViolationListResource::collection($violations)->resolve();

        return view('policemanager.violations.index', [
            'violations' => $violationRows,
            'searchType' => $searchType,
            'searchValue' => $searchValue,
        ]);
    }

    public function heatmap(ViolationHeatmapRequest $request): View
    {
        return view(
            'policemanager.violations.heatmap',
            $this->violationService->getHeatmapPageData($request->validated()),
        );
    }

    public function map(Request $request): View
    {
        return view('policemanager.violations.map', $this->violationService->getReportsMapPageData($request->query()));
    }
}

<?php

namespace App\Http\Controllers\PoliceOfficer;

use App\Http\Controllers\Controller;
use App\Http\Requests\PoliceOfficer\RespondToReportAssignmentRequest;
use App\Http\Requests\PoliceOfficer\UpdateLiveLocationRequest;
use App\Http\Resources\PoliceOfficer\DispatchAssignmentResource;
use App\Http\Services\Dispatch\DispatchService;
use App\Models\CitizenReport;
use App\Models\OfficerLiveLocation;
use App\Models\ReportAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DispatchController extends Controller
{
    public function __construct(
        private readonly DispatchService $dispatchService
    ) {
    }

    public function updateLocation(UpdateLiveLocationRequest $request): JsonResponse
    {
        $user = $request->user();
        $now = now();
        $validated = $request->validated();

        $location = OfficerLiveLocation::query()->firstOrNew([
            'officer_id' => $user->id,
        ]);

        if (! $location->exists) {
            $location->created_at = $now;
        }

        $location->fill([
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'availability_status' => $validated['availability_status'] ?? 'available',
            'last_update_time' => $now,
            'device_id' => $validated['device_id'] ?? null,
            'battery_level' => $validated['battery_level'] ?? null,
            'updated_at' => $now,
        ]);
        $location->save();

        return response()->json([
            'status_code' => 200,
            'message' => 'Location updated successfully',
            'data' => $location,
        ]);
    }

    public function myAssignments(Request $request): JsonResponse
    {
        $this->dispatchService->expireStaleAssignments();

        $assignments = ReportAssignment::query()
            ->with(['citizenReport.reportLocation'])
            ->where('officer_id', $request->user()->id)
            ->whereIn('assignment_status', ['pending', 'accepted'])
            ->orderByDesc('assigned_at')
            ->get();

        return response()->json([
            'status_code' => 200,
            'message' => 'Assignments fetched successfully',
            'data' => DispatchAssignmentResource::collection($assignments),
        ]);
    }

    public function respond(
        RespondToReportAssignmentRequest $request,
        CitizenReport $report
    ): JsonResponse {
        $validated = $request->validated();

        $result = $this->dispatchService->respondToAssignment(
            $report,
            $request->user(),
            $validated['response'],
            $validated['notes'] ?? null
        );

        return response()->json([
            'status_code' => 200,
            'message' => $validated['response'] === 'accept'
                ? 'Assignment accepted successfully'
                : 'Assignment rejected and reassigned when possible',
            'data' => [
                'current_assignment' => new DispatchAssignmentResource($result['assignment']),
                'next_assignment' => $result['next_assignment']
                    ? new DispatchAssignmentResource($result['next_assignment'])
                    : null,
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\PoliceOfficer;

use App\Http\Controllers\Controller;
use App\Http\Requests\PoliceOfficer\CompleteReportAssignmentRequest;
use App\Http\Requests\PoliceOfficer\UpdateLiveLocationRequest;
use App\Http\Resources\PoliceOfficer\DispatchAssignmentResource;
use App\Http\Services\Dispatch\DispatchService;
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
        $hasActiveAssignment = ReportAssignment::query()
            ->where('officer_id', $user->id)
            ->where('assignment_status', 'assigned')
            ->whereHas('citizenReport', function ($query): void {
                $query->whereIn('status', ['dispatched', 'in_progress']);
            })
            ->exists();

        $location = OfficerLiveLocation::query()->firstOrNew([
            'officer_id' => $user->id,
        ]);

        if (! $location->exists) {
            $location->created_at = $now;
        }

        $location->fill([
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'availability_status' => $hasActiveAssignment
                ? 'responding'
                : ($validated['availability_status']
                    ?? ($location->availability_status ?: 'available')),
            'last_update_time' => $now,
            'updated_at' => $now,
        ]);
        $location->save();
        $user->forceFill(['last_seen_at' => $now])->save();

        if ($location->availability_status === 'available') {
            $this->dispatchService->dispatchPendingReportsForOfficer($user->id);
        }

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
            ->where('assignment_status', 'assigned')
            ->orderByDesc('assigned_at')
            ->get();

        return response()->json([
            'status_code' => 200,
            'message' => 'Assignments fetched successfully',
            'data' => DispatchAssignmentResource::collection($assignments),
        ]);
    }

    public function start(
        CompleteReportAssignmentRequest $request,
        ReportAssignment $assignment
    ): JsonResponse {
        $startedAssignment = $this->dispatchService->startAssignment(
            $assignment,
            $request->user(),
            $request->validated()['notes'] ?? null
        );

        return response()->json([
            'status_code' => 200,
            'message' => 'Report marked as in progress successfully',
            'data' => new DispatchAssignmentResource($startedAssignment),
        ]);
    }

    public function complete(
        CompleteReportAssignmentRequest $request,
        ReportAssignment $assignment
    ): JsonResponse {
        $completedAssignment = $this->dispatchService->completeAssignment(
            $assignment,
            $request->user(),
            $request->validated()['notes'] ?? null
        );

        return response()->json([
            'status_code' => 200,
            'message' => 'Report marked as completed successfully',
            'data' => new DispatchAssignmentResource($completedAssignment),
        ]);
    }

}

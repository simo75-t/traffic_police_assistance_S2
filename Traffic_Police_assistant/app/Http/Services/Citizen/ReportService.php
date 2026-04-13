<?php

namespace App\Http\Services\Citizen;

use App\Http\Services\Dispatch\DispatchService;
use App\Models\CitizenReport;
use App\Models\ReportLocation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function __construct(
        private readonly DispatchService $dispatchService
    ) {
    }

    public function createReport(array $data): CitizenReport
    {
        $report = DB::transaction(function () use ($data) {
            $location = ReportLocation::query()->create([
                'address' => $data['address'] ?? null,
                'street_name' => $data['street_name'] ?? null,
                'landmark' => $data['landmark'] ?? null,
                'city' => $data['city'] ?? null,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'created_at' => now(),
            ]);

            $imagePath = isset($data['image']) && $data['image'] instanceof UploadedFile
                ? $this->storeReportImage($data['image'])
                : null;

            return CitizenReport::query()->create([
                'reporter_name' => $data['reporter_name'],
                'reporter_phone' => $data['reporter_phone'] ?? null,
                'reporter_email' => $data['reporter_email'] ?? null,
                'report_location_id' => $location->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'image_path' => $imagePath,
                'status' => 'submitted',
                'priority' => $data['priority'] ?? 'medium',
                'submitted_at' => now(),
                'created_at' => now(),
                'dispatch_attempts_count' => 0,
            ]);
        });

        $this->dispatchService->expireStaleAssignments();
        $this->dispatchService->dispatchReport($report->load('reportLocation'));

        return $report->fresh([
            'reportLocation',
            'assignedOfficer',
            'assignments',
        ]);
    }

    private function storeReportImage(UploadedFile $file): string
    {
        $directory = public_path('uploads/reports');

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $filename = 'report_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'uploads/reports/' . $filename;
    }
}

<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CitizenReport;
use App\Models\OfficerLiveLocation;
use App\Models\ReportAssignment;
use App\Models\User;

$reports = CitizenReport::query()->with(['reportLocation', 'assignedOfficer', 'assignments'])->get();
foreach ($reports as $report) {
    echo "REPORT ID={$report->id} TITLE={$report->title}\n";
    echo " status={$report->status} assigned_officer_id=" . ($report->assigned_officer_id ?? 'NULL') . " assigned_officer=" . ($report->assignedOfficer?->name ?? 'NULL') . "\n";
    echo " location=" . ($report->reportLocation?->latitude ?? 'NULL') . "," . ($report->reportLocation?->longitude ?? 'NULL') . " landmark=" . ($report->reportLocation?->landmark ?? 'NULL') . " address=" . ($report->reportLocation?->address ?? 'NULL') . "\n";
    echo " assignments=" . $report->assignments->count() . "\n";
    foreach ($report->assignments as $assignment) {
        echo "  ASSIGNMENT id={$assignment->id} officer_id={$assignment->officer_id} status={$assignment->assignment_status} order={$assignment->assignment_order}\n";
    }
    echo "---\n";
}

echo "OFFICERS\n";
$officers = User::query()->where('role', 'Police_officer')->get();
foreach ($officers as $officer) {
    $loc = OfficerLiveLocation::query()->where('officer_id', $officer->id)->first();
    echo "officer={$officer->name} id={$officer->id} active={$officer->is_active} ";
    if ($loc) {
        echo "avail={$loc->availability_status} lat={$loc->latitude} lng={$loc->longitude} last_update={$loc->last_update_time}\n";
    } else {
        echo "no_live_location\n";
    }
}

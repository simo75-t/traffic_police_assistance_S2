<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CitizenReport;
use App\Http\Services\Dispatch\DispatchService;

$dispatchService = $app->make(DispatchService::class);
$reports = CitizenReport::query()
    ->with('reportLocation')
    ->whereNull('assigned_officer_id')
    ->whereNotNull('report_location_id')
    ->get();

foreach ($reports as $report) {
    $assignment = $dispatchService->dispatchReport($report->load('reportLocation'));
    echo sprintf("Report id=%d title=%s assigned=%s\n", $report->id, $report->title, $assignment ? 'yes' : 'no');
}

<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\RoleUserEnum;
use App\Models\OfficerLiveLocation;
use Illuminate\Support\Facades\DB;

$results = OfficerLiveLocation::query()
    ->select('officers_live_locations.*', 'users.role', 'users.is_active')
    ->join('users', 'users.id', '=', 'officers_live_locations.officer_id')
    ->where('users.role', RoleUserEnum::Police_officer)
    ->where('users.is_active', true)
    ->where('officers_live_locations.availability_status', 'available')
    ->get();

echo "now=" . now() . "\n";
echo "threshold=" . now()->subSeconds(300) . "\n";

foreach ($results as $row) {
    echo sprintf("id=%d officer_id=%d role=%s active=%s avail=%s last_update=%s lat=%s lng=%s\n", $row->id, $row->officer_id, $row->role, $row->is_active, $row->availability_status, $row->last_update_time, $row->latitude, $row->longitude);
}

echo "count=" . $results->count() . "\n";

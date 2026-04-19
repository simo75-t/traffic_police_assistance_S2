<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\OfficerLiveLocation;

$updated = OfficerLiveLocation::query()
    ->whereIn('availability_status', ['available', 'responding'])
    ->update([
        'last_update_time' => now(),
        'updated_at' => now(),
    ]);
echo "Updated {$updated} officer live locations to now.\n";

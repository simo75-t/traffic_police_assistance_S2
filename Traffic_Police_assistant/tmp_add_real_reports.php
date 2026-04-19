<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Services\Citizen\ReportService;
use App\Http\Services\Dispatch\DispatchService;
use App\Models\CitizenReport;
use Illuminate\Support\Facades\DB;

$reportService = $app->make(ReportService::class);
$dispatchService = $app->make(DispatchService::class);

$existingReport = CitizenReport::query()->where('id', 52)->first();
if ($existingReport) {
    if ($existingReport->assigned_officer_id === null) {
        $dispatched = $dispatchService->dispatchReport($existingReport->load('reportLocation'));
        echo "Existing report dispatch result: " . ($dispatched ? 'assigned' : 'not_assigned') . "\n";
    } else {
        echo "Existing report already assigned to officer_id={$existingReport->assigned_officer_id}\n";
    }
}

$reportsData = [
    [
        'title' => 'مركبة تعيق ممر المشاة',
        'reporter_name' => 'ندى ناصر',
        'reporter_phone' => '0944001112',
        'address' => 'مقابل المخبز في أبو رمانة',
        'street_name' => 'شارع شكري القوتلي',
        'landmark' => 'بالقرب من المخبز',
        'city' => 'دمشق',
        'latitude' => 33.5177520,
        'longitude' => 36.2838140,
        'description' => 'سيارة متوقفة أمام ممر المشاة وتمنع الناس من العبور بأمان.',
        'title' => 'سيارة تعيق ممر المشاة',
        'priority' => 'high',
    ],
    [
        'title' => 'سائق يستخدم الهاتف أثناء القيادة',
        'reporter_name' => 'معتز حمود',
        'reporter_phone' => '0944002223',
        'address' => 'طريق المالكي الرئيسي',
        'street_name' => 'شارع المالكي',
        'landmark' => 'قرب دوار المالكي',
        'city' => 'دمشق',
        'latitude' => 33.5141020,
        'longitude' => 36.2762010,
        'description' => 'رصدت سيارة تسير باستخدام الهاتف على الطريق الرئيسي وتعرض السلامة للخطر.',
        'priority' => 'urgent',
    ],
    [
        'title' => 'مركبة بدون أضواء في الشام الجديدة',
        'reporter_name' => 'رائدة أيوب',
        'reporter_phone' => '0944003334',
        'address' => 'الشارع التجاري في الشام الجديدة',
        'street_name' => 'شارع الشام الجديدة',
        'landmark' => 'بالقرب من فرع المصرف',
        'city' => 'دمشق',
        'latitude' => 33.5291020,
        'longitude' => 36.2319910,
        'description' => 'سيارة تسير بعد غروب الشمس دون تشغيل الأضواء الأمامية في شارع مزدحم.',
        'priority' => 'medium',
    ],
];

foreach ($reportsData as $index => $data) {
    DB::transaction(function () use ($reportService, $data, $index) {
        $created = $reportService->createReport($data);
        echo sprintf("Created report %d id=%d title=%s assigned_officer_id=%s\n", $index + 1, $created->id, $created->title, $created->assigned_officer_id ?? 'NULL');
    });
}

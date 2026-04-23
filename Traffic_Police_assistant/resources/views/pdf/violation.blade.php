<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 16px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            direction: rtl;
            font-size: 13px;
            color: #111;
        }
        .sheet { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .sheet > tbody > tr > td { vertical-align: top; }
        .main, .stub {
            border: 1px solid #000;
            padding: 14px;
        }
        .divider { width: 10px; }
        .title { text-align: right; font-size: 20px; font-weight: bold; }
        .subtitle { text-align: right; font-size: 16px; font-weight: bold; }
        .small-title { text-align: right; font-size: 15px; font-weight: bold; }
        .line-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .line-table td { padding: 4px 0; vertical-align: bottom; }
        .label { font-weight: bold; white-space: nowrap; }
        .line {
            border-bottom: 1px solid #000;
            min-height: 20px;
            padding: 0 4px 2px 4px;
            word-wrap: break-word;
        }
        .box-label { font-weight: bold; margin-top: 10px; margin-bottom: 4px; }
        .box {
            border: 1px solid #000;
            min-height: 88px;
            padding: 8px;
        }
        .footer-table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        .footer-table td { vertical-align: bottom; padding-top: 18px; }
        .signature-line { border-bottom: 1px solid #000; min-height: 20px; padding-bottom: 2px; }
        .muted { margin-top: 10px; line-height: 1.6; }
    </style>
</head>
<body>
@php
    $vehicle = $violation->vehicle;
    $location = $violation->violationLocation;
    $type = $violation->violationType;
    $vehicleSnapshot = is_array($violation->vehicle_snapshot) ? $violation->vehicle_snapshot : (json_decode($violation->vehicle_snapshot ?? 'null', true) ?: []);
    $ownerSnapshot = is_array($violation->owner_snapshot) ? $violation->owner_snapshot : (json_decode($violation->owner_snapshot ?? 'null', true) ?: []);
    $plateSnapshot = is_array($violation->plate_snapshot) ? $violation->plate_snapshot : (json_decode($violation->plate_snapshot ?? 'null', true) ?: []);
    $ownerName = $ownerSnapshot['owner_name'] ?? $ownerSnapshot['name'] ?? $vehicleSnapshot['owner_name'] ?? $vehicle->owner_name ?? '-';
    $plateNumber = $plateSnapshot['plate_number'] ?? $vehicleSnapshot['plate_number'] ?? $vehicle->plate_number ?? '-';
    $vehicleModel = $vehicle->model ?? '-';
    $vehicleColor = $vehicle->color ?? '-';
    $cityName = optional($location->city)->name ?? $location->city ?? '-';
    $street = $location->street_name ?? '-';
    $landmark = $location->landmark ?? '-';
    $address = trim(($cityName !== '-' ? $cityName : '') . ($street && $street !== '-' ? ' - ' . $street : '') . ($landmark && $landmark !== '-' ? ' - ' . $landmark : ''));
    $address = $address !== '' ? $address : '-';
    $lat = $location->latitude ?? '-';
    $lng = $location->longitude ?? '-';
    $occurredAt = optional($violation->occurred_at)->format('Y/m/d - H:i') ?? '-';
    $violationType = $type->name ?? '-';
    $fineAmount = $violation->fine_amount !== null ? $violation->fine_amount : '-';
    $description = $violation->description ?: '-';
@endphp

<table class="sheet">
    <tr>
        <td style="width:72%;">
            <div class="main">
                <table style="width:100%;">
                    <tr>
                        <td class="small-title">قيادة شرطة المرور</td>
                        <td style="text-align:left;">
                            <div class="title">ضبط مخالفة سير</div>
                            <div class="subtitle">رقم {{ $violation->id }}</div>
                        </td>
                    </tr>
                </table>

                <table class="line-table">
                    <tr>
                        <td class="label">في هذا اليوم:</td>
                        <td class="line" style="width:28%;">{{ $occurredAt }}</td>
                        <td class="label">وبناءً على الضبط المنظم بحق المركبة ذات الرقم:</td>
                        <td class="line">{{ $plateNumber }}</td>
                    </tr>
                    <tr>
                        <td class="label">اسم المالك:</td>
                        <td class="line">{{ $ownerName ?: '-' }}</td>
                        <td class="label">نوع المركبة:</td>
                        <td class="line">{{ $vehicleModel ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">لون المركبة:</td>
                        <td class="line">{{ $vehicleColor ?: '-' }}</td>
                        <td class="label">نوع المخالفة:</td>
                        <td class="line">{{ $violationType }}</td>
                    </tr>
                    <tr>
                        <td class="label">قيمة الغرامة:</td>
                        <td class="line">{{ $fineAmount }}</td>
                        <td class="label">المحافظة:</td>
                        <td class="line">{{ $cityName }}</td>
                    </tr>
                    <tr>
                        <td class="label">الشارع:</td>
                        <td class="line">{{ $street }}</td>
                        <td class="label">أقرب دلالة:</td>
                        <td class="line">{{ $landmark }}</td>
                    </tr>
                    <tr>
                        <td class="label">العنوان التفصيلي:</td>
                        <td class="line" colspan="3">{{ $address }}</td>
                    </tr>
                    <tr>
                        <td class="label">الإحداثيات:</td>
                        <td class="line" colspan="3">خط العرض {{ $lat }} - خط الطول {{ $lng }}</td>
                    </tr>
                </table>

                <div class="box-label">وصف المخالفة:</div>
                <div class="box">{{ $description }}</div>

                <div class="muted">
                    لذلك نظمنا هذا الضبط استنادًا إلى البيانات المدخلة في النظام، وبعد الاطلاع على الوقائع المذكورة أعلاه.
                </div>

                <table class="footer-table">
                    <tr>
                        <td style="width:48%;">
                            <div class="label">توقيع المخالف</div>
                            <div class="signature-line">-</div>
                        </td>
                        <td style="width:4%;"></td>
                        <td style="width:48%;">
                            <div class="label">اسم وتوقيع منظم الضبط</div>
                            <div class="signature-line">{{ $officerName }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        </td>
        <td class="divider"></td>
        <td style="width:28%;">
            <div class="stub">
                <div class="small-title">قيادة شرطة المرور</div>
                <div class="subtitle">ضبط مخالفة سير</div>
                <div class="small-title">رقم {{ $violation->id }}</div>

                <table class="line-table" style="margin-top:12px;">
                    <tr>
                        <td class="label">اسم المخالف:</td>
                        <td class="line">{{ $ownerName ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">رقم اللوحة:</td>
                        <td class="line">{{ $plateNumber }}</td>
                    </tr>
                    <tr>
                        <td class="label">نوع المخالفة:</td>
                        <td class="line">{{ $violationType }}</td>
                    </tr>
                    <tr>
                        <td class="label">قيمة الغرامة:</td>
                        <td class="line">{{ $fineAmount }}</td>
                    </tr>
                    <tr>
                        <td class="label">المكان:</td>
                        <td class="line">{{ $cityName }} - {{ $street }}</td>
                    </tr>
                    <tr>
                        <td class="label">التاريخ:</td>
                        <td class="line">{{ $occurredAt }}</td>
                    </tr>
                </table>

                <table class="footer-table">
                    <tr>
                        <td>
                            <div class="label">اسم وتوقيع منظم الضبط</div>
                            <div class="signature-line">{{ $officerName }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>
</body>
</html>

@extends('policemanager.layouts.app')

@section('title', 'خريطة البلاغات')
@section('page_title', 'خريطة بلاغات المواطنين')
@section('page_description', 'متابعة بلاغات المواطنين حسب الموقع، الحالة الحالية، وحالة التوزيع.')

@section('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        .map-legend {
            min-width: 190px;
            padding: 14px 16px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
            font-size: 0.92rem;
            line-height: 1.45;
        }
        .map-legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        .map-legend-swatch {
            width: 14px;
            height: 14px;
            border-radius: 4px;
            display: inline-block;
            border: 1px solid rgba(15, 23, 42, 0.12);
        }
        .report-table-wrapper {
            overflow-x: auto;
        }
        .report-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 680px;
            font-size: 0.95rem;
            background: #ffffff;
        }
        .report-table th,
        .report-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            vertical-align: middle;
        }
        .report-table th {
            background: #f8fafc;
            color: #0f172a;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .report-table tbody tr:hover {
            background: #f8fafc;
        }
        .report-status-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 0.85rem;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.86rem;
            white-space: nowrap;
        }
        .map-filters {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }
        .map-filters label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.86rem;
            font-weight: 700;
            color: #334155;
        }
        .map-filters input,
        .map-filters select {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 12px;
            background: #fff;
        }
        .map-filters-actions {
            display: flex;
            align-items: end;
            gap: 10px;
        }
        .map-filters-actions .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }
        .info-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px;
            background: #fff;
        }
        .info-card h4 {
            margin: 0 0 10px;
            font-size: 1rem;
        }
        .info-card p,
        .info-card ul {
            margin: 0;
            color: #475569;
            line-height: 1.7;
        }
        .info-card ul {
            padding-left: 18px;
        }
        .status-submitted { background: #fef3c7; color: #92400e; }
        .status-dispatched { background: #ede9fe; color: #5b21b6; }
        .status-in-progress { background: #fee2e2; color: #b91c1c; }
        .status-closed { background: #dcfce7; color: #166534; }
        .status-unknown { background: #e2e8f0; color: #475569; }
        @media (max-width: 1100px) {
            .map-filters {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 640px) {
            .map-filters {
                grid-template-columns: 1fr;
            }
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    @php
        $statusLabels = [
            'submitted' => 'مُقدّم',
            'dispatched' => 'تم التوجيه',
            'in_progress' => 'قيد المعالجة',
            'closed' => 'مغلق',
            'unknown' => 'غير معروف',
        ];

        $assignmentStateLabels = [
            'assigned' => 'تم التعيين',
            'unassigned' => 'غير معيّن',
        ];

    @endphp

    <section class="stack">
        <div class="map-meta-grid">
            <article class="map-meta-card">
                <span class="map-card-label">البلاغات الظاهرة</span>
                <strong>{{ $summary['totalReports'] }}</strong>
            </article>
            <article class="map-meta-card">
                <span class="map-card-label">البلاغات المفتوحة</span>
                <strong>{{ $summary['pendingReports'] }}</strong>
            </article>
            <article class="map-meta-card">
                <span class="map-card-label">البلاغات المعيّنة</span>
                <strong>{{ $summary['assignedReports'] }}</strong>
            </article>
            <article class="map-meta-card">
                <span class="map-card-label">البلاغات المغلقة</span>
                <strong>{{ $summary['closedReports'] }}</strong>
            </article>
        </div>

        <article class="surface">
            <div class="surface-body">
                <h3>الفلاتر</h3>
                <form method="GET" action="{{ route('policemanager.violations.map') }}">
                    <div class="map-filters">
                        <div>
                            <label for="status">حالة البلاغ</label>
                            <select id="status" name="status">
                                <option value="">كل الحالات</option>
                                @foreach (['submitted', 'dispatched', 'in_progress', 'closed'] as $statusOption)
                                    <option value="{{ $statusOption }}" @selected(($filters['status'] ?? '') === $statusOption)>
                                        {{ $statusLabels[$statusOption] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="assignment">حالة التوزيع</label>
                            <select id="assignment" name="assignment">
                                <option value="">كل حالات التوزيع</option>
                                <option value="assigned" @selected(($filters['assignment'] ?? '') === 'assigned')>تم التعيين</option>
                                <option value="unassigned" @selected(($filters['assignment'] ?? '') === 'unassigned')>غير معيّن</option>
                            </select>
                        </div>
                        <div>
                            <label for="city">المدينة</label>
                            <input id="city" name="city" type="text" value="{{ $filters['city'] ?? '' }}" placeholder="دمشق" />
                        </div>
                        <div class="map-filters-actions">
                            <button class="btn btn-primary" type="submit">تطبيق</button>
                            <a class="btn btn-secondary" href="{{ route('policemanager.violations.map') }}">إعادة ضبط</a>
                        </div>
                    </div>
                </form>
            </div>
        </article>

     

        <article class="surface">
            <div class="surface-body">
                <h3>خريطة البلاغات</h3>
                <p>كل نقطة تمثل بلاغ مواطن، ويتم تلوينها حسب حالتها الحالية.</p>
                <div id="reports-map" class="map-panel"></div>
            </div>
        </article>

        <article class="surface">
            <div class="surface-body">
                <h3>تفاصيل البلاغات</h3>
                <div class="report-table-wrapper">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>العنوان</th>
                                <th>الأولوية</th>
                                <th>المبلّغ</th>
                                <th>الموقع</th>
                                <th>حالة البلاغ</th>
                                <th>تاريخ الإرسال</th>
                                <th>تاريخ الإغلاق</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reportsMap as $report)
                                <tr>
                                    <td>{{ $report['title'] ?: 'بلاغ بدون عنوان' }}</td>
                                    <td>{{ $report['priority'] ?: '-' }}</td>
                                    <td>{{ $report['reporter_name'] ?: '-' }}</td>
                                    <td>{{ $report['location_summary'] ?: '-' }}</td>
                                    <td>
                                        <span class="report-status-chip status-{{ \Illuminate\Support\Str::slug($report['status'] ?? 'unknown', '-') }}">
                                            {{ $statusLabels[$report['status'] ?? 'unknown'] ?? 'غير معروف' }}
                                        </span>
                                    </td>
                                    <td>{{ $report['submitted_at'] ?: '-' }}</td>
                                    <td>{{ $report['closed_at'] ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="empty-state">لا توجد بلاغات مطابقة للفلاتر الحالية.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </article>

        <article class="surface">
            <div class="surface-body">
                <h3>نظرة عامة على التوزيع</h3>
                <div class="report-table-wrapper">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>العنوان</th>
                                <th>العنصر المكلف</th>
                                <th>حالة التوزيع</th>
                                <th>الموقع</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reportsMap as $report)
                                <tr>
                                    <td>{{ $report['title'] ?: 'بلاغ بدون عنوان' }}</td>
                                    <td>{{ $report['assigned_officer'] ?: 'غير معيّن' }}</td>
                                    <td>{{ $assignmentStateLabels[$report['assignment_state'] ?? 'unassigned'] ?? 'غير معيّن' }}</td>
                                    <td>{{ $report['location_summary'] ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="empty-state">لا توجد بلاغات مطابقة للفلاتر الحالية.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </article>
    </section>
@endsection

@section('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const reports = @json($reportsMap);

        const statusColors = {
            submitted: '#f59e0b',
            dispatched: '#8b5cf6',
            in_progress: '#ef4444',
            closed: '#10b981',
            unknown: '#64748b',
        };

        const statusLabels = {
            submitted: 'مُقدّم',
            dispatched: 'تم التوجيه',
            in_progress: 'قيد المعالجة',
            closed: 'مغلق',
            unknown: 'غير معروف',
        };

        const reportStatusKeys = ['submitted', 'dispatched', 'in_progress', 'closed'];

        const assignmentStateLabels = {
            assigned: 'تم التعيين',
            unassigned: 'غير معيّن',
        };

        const mapContainer = document.getElementById('reports-map');
        const defaultCenter = [33.5138, 36.2765];
        const map = L.map(mapContainer).setView(defaultCenter, 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        const isValidCoordinate = (value) => {
            return value !== null && value !== undefined && value !== '' && !Number.isNaN(Number(value));
        };

        let hasMarkers = false;
        const markerIndexByLocation = {};
        const markerPositions = [];

        reports.forEach(function(report) {
            if (!report.location || !isValidCoordinate(report.location.latitude) || !isValidCoordinate(report.location.longitude)) {
                return;
            }

            hasMarkers = true;
            const rawLat = Number(report.location.latitude);
            const rawLng = Number(report.location.longitude);
            const locationKey = `${rawLat.toFixed(7)},${rawLng.toFixed(7)}`;
            const occurrence = markerIndexByLocation[locationKey] || 0;
            markerIndexByLocation[locationKey] = occurrence + 1;

            let markerLat = rawLat;
            let markerLng = rawLng;
            if (occurrence > 0) {
                const offsetDistance = 0.00008 * Math.ceil(occurrence / 8);
                const angle = (occurrence - 1) * 45 * (Math.PI / 180);
                markerLat += Math.sin(angle) * offsetDistance;
                markerLng += Math.cos(angle) * offsetDistance;
            }

            markerPositions.push([markerLat, markerLng]);

            const reportStatusKey = report.status || 'unknown';
            const markerColor = statusColors[reportStatusKey] || statusColors.unknown;
            const marker = L.circleMarker([markerLat, markerLng], {
                radius: 10,
                fillColor: markerColor,
                color: '#ffffff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.9,
            }).addTo(map);

            const title = report.title || 'بلاغ جديد';
            const officer = report.assigned_officer || 'غير معيّن';
            const status = statusLabels[reportStatusKey] || reportStatusKey.replace(/_/g, ' ');
            const assignment = assignmentStateLabels[report.assignment_state || 'unassigned'] || 'غير معيّن';
            const locationText = report.location_summary || report.location.city || 'موقع غير معروف';

            marker.bindPopup(`
                <div style="font-family: Cairo, sans-serif; line-height:1.45;">
                    <strong>#${report.id} - ${title}</strong>
                    <div style="margin-top:6px; color:#475569;">${locationText}</div>
                    <div style="margin-top:8px; font-size:0.94rem;">
                        <div><strong>الشرطي المكلف:</strong> ${officer}</div>
                        <div><strong>الحالة:</strong> ${status}</div>
                        <div><strong>التوزيع:</strong> ${assignment}</div>
                    </div>
                </div>
            `);
        });

        if (hasMarkers && markerPositions.length > 0) {
            map.fitBounds(L.latLngBounds(markerPositions).pad(0.15));
        }

        const legend = L.control({ position: 'bottomright' });
        legend.onAdd = function () {
            const div = L.DomUtil.create('div', 'map-legend');
            div.innerHTML = '<h4>حالة البلاغ</h4>' +
                reportStatusKeys.map((key) =>
                    `<div class="map-legend-item"><span class="map-legend-swatch" style="background:${statusColors[key]}"></span><span>${statusLabels[key]}</span></div>`
                ).join('');
            return div;
        };
        legend.addTo(map);
    </script>
@endsection
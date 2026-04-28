@extends('policemanager.layouts.app')



@section('content')
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""
    >
    <link rel="stylesheet" href="{{ asset('pm-assets/heatmap.css') }}">

    <div
        class="heatmap-page"
        id="heatmap-app"
        data-generate-url="{{ route('policemanager.heatmap.generate') }}"
        data-result-url-template="{{ route('policemanager.heatmap.result', ['job_id' => '__JOB_ID__']) }}"
        data-prediction-generate-url="{{ route('policemanager.heatmap.prediction.generate') }}"
        data-prediction-result-url-template="{{ route('policemanager.heatmap.prediction.result', ['job_id' => '__JOB_ID__']) }}"
        data-initial-filters='@json($filters)'
    >
        <section class="dashboard-toolbar">
            <div class="dashboard-toolbar__filters">
                 </div>
            <div class="dashboard-toolbar__summary">
                <h1>الخريطة الحرارية للمخالفات</h1>
                <p>تحليل المناطق الساخنة وتوصيات توزيع الدوريات</p>
            </div>
                <div class="toolbar-pill">
                    <span class="toolbar-pill__label">الفترة الزمنية</span>
                    <strong id="timeline-range">-</strong>
                </div>
                <div class="toolbar-pill">
                    <span class="toolbar-pill__label">المدينة</span>
                    <strong id="timeline-city">-</strong>
                </div>
                <button id="poll-button" type="button" class="toolbar-pill toolbar-pill--action">
                    <span class="toolbar-pill__label">النتائج</span>
                    <strong>تحديث النتائج</strong>
                </button>
           
        </section>

        <section class="heatmap-panel heatmap-panel--filters">
            <div class="panel-heading">
                <div>
                    <h2> الفلاتر</h2>
                    <p>حدد المدينة والفترة ونوع المخالفة ثم اطلب التحليل والموجز التشغيلي.</p>
                </div>
                
            </div>

            <form id="heatmap-form" class="heatmap-filters heatmap-filters--dashboard">
                <div class="field">
                    <label for="city">المدينة</label>
                    <select id="city" name="city" required>
                        <option value="">اختر المدينة</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->name }}" @selected(($filters['city'] ?? '') === $city->name)>{{ $city->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="date_from">من تاريخ</label>
                    <input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" required>
                </div>

                <div class="field">
                    <label for="date_to">إلى تاريخ</label>
                    <input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" required>
                </div>

                <div class="field">
                    <label for="violation_type_id">نوع المخالفة</label>
                    <select id="violation_type_id" name="violation_type_id">
                        <option value="">كل الأنواع</option>
                        @foreach($violationTypes as $type)
                            <option value="{{ $type->id }}" @selected((string) ($filters['violation_type_id'] ?? '') === (string) $type->id)>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="time_bucket">الفترة الزمنية</label>
                    <select id="time_bucket" name="time_bucket">
                        @foreach($timeBucketOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['time_bucket'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="comparison_mode">المقارنة</label>
                    <select id="comparison_mode" name="comparison_mode">
                        @foreach($comparisonModeOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['comparison_mode'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field field--toggles">
                    <label>خيارات إضافية</label>
                    <label class="toggle">
                        <input type="checkbox" name="include_ranking" value="1" @checked(($filters['include_ranking'] ?? '0') === '1')>
                        <span>إظهار ترتيب المناطق</span>
                    </label>
                    <label class="toggle">
                        <input type="checkbox" name="include_trend" value="1" @checked(($filters['include_trend'] ?? '0') === '1')>
                        <span>إظهار اتجاه المخالفات</span>
                    </label>
                </div>

                <input type="hidden" name="grid_size_meters" value="{{ $filters['grid_size_meters'] ?? '300' }}">

                <div class="heatmap-filters__actions">
                    <button id="generate-button" type="submit" class="btn btn-primary">توليد الخريطة الحرارية</button>
                    <button id="prediction-button" type="button" class="btn btn-secondary" disabled>توليد توصيات الذكاء الاصطناعي</button>
                    <button id="prediction-poll-button" type="button" class="btn btn-secondary" disabled>تحديث التوصيات</button>
                </div>
            </form>
        </section>

        <section class="kpi-grid">
            <article class="kpi-card">
                <span class="kpi-card__label">الفترة الزمنية</span>
                <strong class="kpi-card__value" id="metric-period">-</strong>
                <p class="kpi-card__hint">الفترة المعتمدة في التحليل الحالي.</p>
            </article>

            <article class="kpi-card">
                <span class="kpi-card__label">إجمالي المخالفات</span>
                <strong class="kpi-card__value" id="metric-total-violations">0</strong>
                <p class="kpi-card__hint">عدد السجلات الداخلة في الخريطة الحرارية.</p>
            </article>

            <article class="kpi-card">
                <span class="kpi-card__label">مناطق حرجة</span>
                <strong class="kpi-card__value" id="metric-critical-areas">0</strong>
                <p class="kpi-card__hint">عدد المناطق التي تتطلب تدخلًا سريعًا.</p>
            </article>

            <article class="kpi-card">
                <span class="kpi-card__label">مستوى خطر التوقع</span>
                <strong class="kpi-card__value" id="metric-prediction-risk">-</strong>
                <p class="kpi-card__hint">يتحدث بعد توليد توصيات الذكاء الاصطناعي.</p>
            </article>

            <article class="kpi-card">
                <span class="kpi-card__label">مصدر التوصيات</span>
                <strong class="kpi-card__value" id="metric-prediction-source">-</strong>
                <p class="kpi-card__hint">يوضح ما إذا كانت النتيجة من Ollama أو من fallback.</p>
            </article>
        </section>

        <section class="heatmap-layout" id="heatmap-results">
            <section class="heatmap-panel heatmap-panel--map">
                <div class="panel-heading panel-heading--map">
                    <div>
                        <h2>الخريطة الحرارية</h2>
                        <p>الخريطة تعرض تركّز المخالفات بصريًا مع الإبقاء على أدوات التحكم الحالية.</p>
                    </div>
                    <div class="heatmap-legend">
                        <span>منخفض</span>
                        <div class="heatmap-legend__bar"></div>
                        <span>عالي</span>
                    </div>
                </div>

                
                <div id="heatmap-stage" class="heatmap-stage">
                    <div id="heatmap-map" class="heatmap-map"></div>
                    <div id="heatmap-feedback" class="heatmap-empty heatmap-empty--overlay">
                        <div class="heatmap-empty__icon">!</div>
                        <h2>لا توجد نتيجة بعد</h2>
                        <p>ابدأ بتوليد الخريطة الحرارية لعرض مناطق الخطورة والاتجاهات.</p>
                    </div>
                </div>
            </section>

            <aside class="heatmap-stack">
                <section class="heatmap-panel">
                    <div class="panel-heading panel-heading--compact">
                        <div>
                            <h2>المناطق ذات الأولوية</h2>
                            <p>أهم المناطق التي تستحق تركيز الدوريات والضبط المروري.</p>
                        </div>
                    </div>
                    <div id="ranking-list" class="priority-list">
                        <div class="empty-state">ستظهر هنا قائمة مناطق الأولوية بعد اكتمال التحليل.</div>
                    </div>
                </section>

                <section class="heatmap-panel">
                    <div class="panel-heading panel-heading--compact">
                        <div>
                            <h2>المنطقة المحددة</h2>
                            <p>تفاصيل سريعة عند الضغط على أي منطقة في الخريطة.</p>
                        </div>
                    </div>

                    <div class="selected-area-card">
                        <div class="selected-area-card__row">
                            <span>المنطقة</span>
                            <strong id="detail-area">-</strong>
                        </div>
                        <div class="selected-area-card__row">
                            <span>شدة الخطورة</span>
                            <strong id="detail-intensity">-</strong>
                        </div>
                        <div class="selected-area-card__row">
                            <span>مستوى الخطر</span>
                            <strong id="detail-risk">-</strong>
                        </div>
                    </div>

                    <details class="technical-details">
                        <summary>تفاصيل تقنية</summary>
                        <div class="technical-details__grid">
                            <div><span>معرف العملية</span><strong id="metric-job-id">لم يبدأ</strong></div>
                            <div><span>معرف الخلية</span><strong id="detail-cell-id">-</strong></div>
                            <div><span>خط العرض</span><strong id="detail-lat">-</strong></div>
                            <div><span>خط الطول</span><strong id="detail-lng">-</strong></div>
                        </div>
                    </details>
                </section>
            </aside>
        </section>

        <section class="heatmap-secondary-grid">
            <section class="heatmap-panel prediction-panel is-hidden" id="prediction-panel">
                <div class="panel-heading">
                    <div>
                        <h2>توصيات  للدوريات</h2>
                        <p>ملخص تنفيذي وإجراءات تشغيلية موجهة لمدير الشرطة.</p>
                    </div>
                    <div class="status-inline status-inline--soft">
                        <span class="status-inline__label">مصدر التوصيات</span>
                        <strong id="prediction-source-badge">-</strong>
                    </div>
                </div>

                <div class="executive-summary">
                    <div class="executive-summary__meta">
                        <span>الملخص التنفيذي</span>
                        <strong id="prediction-summary">-</strong>
                    </div>
                    <div class="executive-summary__job">
                        <span>معرف المهمة</span>
                        <strong id="prediction-job-id">-</strong>
                    </div>
                    <div class="executive-summary__job">
                        <span>حالة التوقع</span>
                        <strong id="prediction-status">-</strong>
                    </div>
                </div>

                <div class="recommendations-block">
                    <h3>المناطق المتوقع ارتفاع مخاطرها</h3>
                    <div id="prediction-hotspots-list" class="priority-list">
                        <div class="empty-state">ستظهر هنا المناطق المتوقعة بعد توليد التوصيات.</div>
                    </div>
                </div>

                <div class="recommendations-block">
                    <h3>الإجراءات المقترحة</h3>
                    <div id="prediction-recommendations-list" class="recommendation-list">
                        <div class="empty-state">ستظهر هنا توصيات توزيع الدوريات بعد توليدها.</div>
                    </div>
                </div>

                <div id="prediction-limitations-list" class="limitations-list is-hidden">
                    <div class="empty-state">ستظهر هنا الملاحظات عند الحاجة.</div>
                </div>
            </section>

            <section class="heatmap-panel">
                <div class="panel-heading">
                    <div>
                        <h2>اتجاه المخالفات مقارنة بالفترة السابقة</h2>
                        <p>عرض موجز للتغير في المناطق ذات التأثير الأعلى.</p>
                    </div>
                </div>
                <div id="trend-list" class="trend-list">
                    <div class="empty-state">ستظهر الاتجاهات هنا بعد تشغيل التحليل المقارن.</div>
                </div>
            </section>
        </section>
    </div>

    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""
    ></script>
    <script src="{{ asset('pm-assets/heatmap.js') }}"></script>
@endsection

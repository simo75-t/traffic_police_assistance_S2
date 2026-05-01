<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>بوابة المواطن</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #1d466e;
            --blue: #50b7e8;
            --blue-soft: #e9f8ff;
            --gold: #ffd44f;
            --gold-soft: #fff6cf;
            --line: rgba(29, 70, 110, 0.12);
            --text: #1f3550;
            --muted: #6d85a1;
            --surface: rgba(255, 255, 255, 0.96);
            --success: #17885c;
            --danger: #cc4b46;
            --warning: #b87b00;
            --shadow: 0 20px 45px rgba(31, 76, 114, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Cairo", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(80, 183, 232, 0.20), transparent 26%),
                radial-gradient(circle at top right, rgba(255, 212, 79, 0.18), transparent 24%),
                linear-gradient(180deg, #f6fbff 0%, #ffffff 58%, #eff8ff 100%);
        }

        a {
            color: inherit;
        }

        .page {
            max-width: 1240px;
            margin: 0 auto;
            padding: 28px 18px 36px;
        }

        .hero {
            display: grid;
            gap: 14px;
            margin-bottom: 22px;
        }

        .hero h1 {
            margin: 0;
            font-size: clamp(2rem, 4vw, 3.2rem);
            line-height: 1.2;
            color: var(--navy);
        }

        .hero p {
            margin: 0;
            max-width: 760px;
            font-size: 1.02rem;
            color: var(--muted);
        }

        .hero-badge {
            width: 116px;
            height: 8px;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--blue), #a8e5ff 55%, var(--gold));
        }

        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(320px, 0.85fr);
            gap: 20px;
            align-items: start;
        }

        .stack {
            display: grid;
            gap: 20px;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 28px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-body {
            padding: 24px;
        }

        .section-head {
            display: grid;
            gap: 8px;
            margin-bottom: 22px;
        }

        .section-head h2 {
            margin: 0;
            font-size: 1.7rem;
            color: var(--navy);
        }

        .section-head p {
            margin: 0;
            color: var(--muted);
            line-height: 1.8;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px 18px;
        }

        .field,
        .field-full {
            display: grid;
            gap: 8px;
        }

        .field-full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 0.98rem;
            font-weight: 700;
            color: var(--navy);
        }

        input,
        select,
        textarea,
        button {
            font: inherit;
        }

        input,
        select,
        textarea {
            width: 100%;
            border: 1px solid rgba(80, 183, 232, 0.28);
            border-radius: 18px;
            padding: 14px 16px;
            background: #fff;
            color: var(--text);
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 4px rgba(80, 183, 232, 0.14);
        }

        textarea {
            min-height: 126px;
            resize: vertical;
        }

        .hint {
            font-size: 0.88rem;
            color: var(--muted);
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }

        .btn {
            border: 0;
            border-radius: 14px;
            padding: 13px 20px;
            cursor: pointer;
            font-weight: 700;
            transition: transform 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            color: #11304f;
            background: linear-gradient(135deg, var(--gold), #ffe279);
            box-shadow: 0 14px 24px rgba(255, 212, 79, 0.22);
        }

        .btn-secondary {
            color: #fff;
            background: linear-gradient(135deg, var(--blue), #2ea9e1);
            box-shadow: 0 14px 24px rgba(80, 183, 232, 0.22);
        }

        .btn-outline {
            color: var(--navy);
            background: #fff;
            border: 1px solid rgba(29, 70, 110, 0.16);
        }

        .status-box {
            border-radius: 18px;
            padding: 15px 16px;
            line-height: 1.8;
            border: 1px solid rgba(29, 70, 110, 0.08);
            background: #f8fcff;
        }

        .status-box[data-tone="info"] {
            color: var(--navy);
            background: #f4fbff;
        }

        .status-box[data-tone="success"] {
            color: var(--success);
            background: #effbf5;
            border-color: rgba(23, 136, 92, 0.16);
        }

        .status-box[data-tone="warning"] {
            color: var(--warning);
            background: #fff9e9;
            border-color: rgba(184, 123, 0, 0.16);
        }

        .status-box[data-tone="error"] {
            color: var(--danger);
            background: #fff3f2;
            border-color: rgba(204, 75, 70, 0.16);
        }

        .preview-box {
            min-height: 220px;
            display: grid;
            place-items: center;
            text-align: center;
            border: 1px dashed rgba(80, 183, 232, 0.35);
            border-radius: 22px;
            background:
                linear-gradient(145deg, rgba(80, 183, 232, 0.07), rgba(255, 212, 79, 0.07)),
                #fcfeff;
            color: var(--muted);
            padding: 20px;
        }

        .preview-box img {
            width: 100%;
            height: 100%;
            max-height: 280px;
            object-fit: cover;
            border-radius: 18px;
            display: block;
        }

        .location-summary {
            display: grid;
            gap: 10px;
        }

        .info-list {
            display: grid;
            gap: 10px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 16px;
            background: #f8fbff;
            border: 1px solid rgba(29, 70, 110, 0.08);
        }

        .info-item strong {
            color: var(--navy);
        }

        .timeline {
            display: grid;
            gap: 10px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .timeline li {
            position: relative;
            padding: 12px 14px 12px 16px;
            border-radius: 16px;
            border: 1px solid rgba(29, 70, 110, 0.08);
            background: #fff;
        }

        .timeline small {
            display: block;
            color: var(--muted);
            margin-top: 2px;
        }

        .results {
            display: grid;
            gap: 14px;
            margin-top: 18px;
        }

        .result-card {
            padding: 18px;
            border-radius: 18px;
            border: 1px solid rgba(29, 70, 110, 0.10);
            background: #fff;
        }

        .result-card h3 {
            margin: 0 0 10px;
            font-size: 1.1rem;
            color: var(--navy);
        }

        .result-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px 14px;
            margin-bottom: 12px;
        }

        .result-meta div {
            padding: 10px 12px;
            border-radius: 14px;
            background: #f8fbff;
        }

        .result-meta strong {
            display: block;
            margin-bottom: 4px;
            color: var(--navy);
        }

        .result-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.9;
        }

        .empty-state {
            padding: 18px;
            text-align: center;
            color: var(--muted);
            border: 1px dashed rgba(29, 70, 110, 0.18);
            border-radius: 18px;
            background: #fcfeff;
        }

        @media (max-width: 980px) {
            .layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .page {
                padding-inline: 14px;
            }

            .card-body {
                padding: 18px;
            }

            .grid,
            .result-meta {
                grid-template-columns: 1fr;
            }

            .info-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <header class="hero">
            <h1>بوابة المواطن لتقديم البلاغات</h1>
            <p>أبلغ عن المخالفات المرورية والحوادث بسرعة وسهولة. سجّل بلاغك مع الموقع والصورة لسرعة المتابعة.</p>
            <div class="hero-badge" aria-hidden="true"></div>
        </header>

        <div class="layout">
            <main class="stack">
                <section class="card">
                    <div class="card-body">
                        <div class="section-head">
                            <h2>تقديم بلاغ جديد</h2>
                            <p>املأ النموذج أدناه لرفع بلاغ عن مخالفة أو حادث مروري. تأكد من تحديد الموقع وكتابة التفاصيل بدقة.</p>
                        </div>

                        <form id="reportForm" enctype="multipart/form-data" novalidate>
                            <div class="grid">
                                <div class="field">
                                    <label for="reportTitle">عنوان البلاغ</label>
                                    <input id="reportTitle" name="title" type="text" maxlength="255" placeholder="مثال: تخطي الإشارة الحمراء" required>
                                </div>

                                <div class="field">
                                    <label for="priority">درجة الأهمية</label>
                                    <select id="priority" name="priority" required>
                                        <option value="medium" selected>متوسطة</option>
                                        <option value="low">منخفضة</option>
                                        <option value="high">عالية</option>
                                        <option value="urgent">عاجلة</option>
                                    </select>
                                </div>

                                <div class="field">
                                    <label for="reporterName">اسم المبلغ</label>
                                    <input id="reporterName" name="reporter_name" type="text" maxlength="255" placeholder="مثال: محمد أحمد" required>
                                </div>

                                <div class="field">
                                    <label for="reporterPhone">رقم الهاتف</label>
                                    <input id="reporterPhone" name="reporter_phone" type="tel" maxlength="50" placeholder="09xxxxxxxx">
                                </div>

                                <div class="field-full">
                                    <label for="reportDescription">وصف البلاغ</label>
                                    <textarea id="reportDescription" name="description" placeholder="اكتب وصفًا واضحًا للحادث أو المخالفة واذكر التفاصيل المهمة مثل نوع المخالفة والمكان." required></textarea>
                                </div>

                                <div class="field-full">
                                    <label for="addressDescription">العنوان التفصيلي</label>
                                    <input id="addressDescription" name="address" type="text" maxlength="255" placeholder="مثال: شارع الملك فيصل، بجانب دوار النور" required>
                                    <div class="hint">اكتب العنوان بأكبر قدر من الدقة لتسهيل وصول الجهات المختصة.</div>
                                </div>

                                <div class="field">
                                    <label for="streetName">اسم الشارع</label>
                                    <input id="streetName" name="street_name" type="text" maxlength="255" placeholder="مثال: شارع الملك فيصل">
                                </div>

                                <div class="field">
                                    <label for="landmark">معلم بارز</label>
                                    <input id="landmark" name="landmark" type="text" maxlength="255" placeholder="مثال: بجانب محطة الوقود">
                                </div>

                                <div class="field-full">
                                    <label for="image">إضافة صورة البلاغ</label>
                                    <input id="image" name="image" type="file" accept="image/*">
                                    <div class="hint">يمكنك رفع صورة توضح المخالفة أو الحادث. الحد الأقصى الموصى به هو 10 ميجابايت.</div>
                                </div>
                            </div>

                            <input id="latitude" name="latitude" type="hidden" required>
                            <input id="longitude" name="longitude" type="hidden" required>

                            <div class="actions">
                                <button class="btn btn-secondary" type="button" id="captureLocationBtn">تحديد الموقع الآن</button>
                                <button class="btn btn-primary" type="submit" id="submitReportBtn">إرسال البلاغ</button>
                            </div>
                        </form>
                    </div>
                </section>

                <section class="card">
                    <div class="card-body">
                        <div class="section-head">
                            <h2>البحث عن مخالفة</h2>
                            <p>ابحث برقم اللوحة لمعرفة حالة البلاغ أو تفاصيل المخالفة المرتبطة به.</p>
                        </div>

                        <form id="searchForm" novalidate>
                            <div class="grid">
                                <div class="field-full">
                                    <label for="plateNumber">رقم اللوحة</label>
                                    <input id="plateNumber" name="plate" type="text" placeholder="مثال: 123456" required>
                                </div>
                            </div>

                            <div class="actions">
                                <button class="btn btn-outline" type="submit" id="searchBtn">بحث</button>
                            </div>
                        </form>

                        <div id="searchStatus" class="status-box" data-tone="info" style="margin-top: 18px;">
                            استخدم نموذج البحث لإيجاد بلاغ برقم اللوحة.
                        </div>

                        <div id="searchResults" class="results"></div>
                    </div>
                </section>
            </main>

            <aside class="stack">
                <section class="card">
                    <div class="card-body">
                        <div class="section-head">
                            <h2>معاينة الصورة والموقع</h2>
                            <p>يمكنك الاطلاع على الصورة المرفوعة وموقع الإبلاغ للتأكد من المعلومات قبل الإرسال.</p>
                        </div>

                        <div id="imagePreview" class="preview-box">
                            هنا سيظهر معاينة الصورة بعد اختيار ملف.
                        </div>

                        <div class="location-summary" style="margin-top: 16px;">
                            <div class="info-list">
                                <div class="info-item">
                                    <strong>خط العرض</strong>
                                    <span id="latitudeText">لم يتم تحديد الموقع بعد</span>
                                </div>
                                <div class="info-item">
                                    <strong>خط الطول</strong>
                                    <span id="longitudeText">لم يتم تحديد الموقع بعد</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <div class="card-body">
                        <div class="section-head">
                            <h2>حالة البلاغ</h2>
                            <p>ستظهر هنا آخر حالة للبلاغ مع التسلسل الزمني للمستجدات بعد الإرسال.</p>
                        </div>

                        <div id="reportStatus" class="status-box" data-tone="info">
                            لم يتم إرسال أي بلاغ بعد.
                        </div>

                        <ul id="reportTimeline" class="timeline" style="margin-top: 16px;">
                            <li>سيتم عرض خطوات معالجة البلاغ هنا.</li>
                        </ul>
                    </div>
                </section>
            </aside>
        </div>
    </div>

    <script>
        const reportForm = document.getElementById('reportForm');
        const searchForm = document.getElementById('searchForm');
        const reportStatus = document.getElementById('reportStatus');
        const searchStatus = document.getElementById('searchStatus');
        const reportTimeline = document.getElementById('reportTimeline');
        const searchResults = document.getElementById('searchResults');
        const captureLocationBtn = document.getElementById('captureLocationBtn');
        const submitReportBtn = document.getElementById('submitReportBtn');
        const searchBtn = document.getElementById('searchBtn');
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');
        const latitudeText = document.getElementById('latitudeText');
        const longitudeText = document.getElementById('longitudeText');
        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('imagePreview');

        function setStatus(element, tone, message) {
            element.dataset.tone = tone;
            element.textContent = message;
        }

        function nowLabel() {
            return new Date().toLocaleString('ar-SY', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        }

        function getReportStatusLabel(status) {
            const labels = {
                submitted: 'تم استلام البلاغ',
                dispatched: 'تم إرسال دورية',
                in_progress: 'قيد المعالجة',
                closed: 'تم إغلاق البلاغ',
            };

            return labels[status] || 'غير معروف';
        }

        function renderTimeline(items) {
            reportTimeline.innerHTML = '';

            items.forEach((item) => {
                const li = document.createElement('li');
                li.textContent = item.title;

                const small = document.createElement('small');
                small.textContent = item.detail;
                li.appendChild(small);

                reportTimeline.appendChild(li);
            });
        }

        function renderSelectedImage(file) {
            if (!file) {
                imagePreview.textContent = 'هنا سيظهر معاينة الصورة بعد اختيار ملف.';
                return;
            }

            const reader = new FileReader();
            reader.onload = (event) => {
                imagePreview.innerHTML = `<img src="${event.target?.result || ''}" alt="صورة البلاغ">`;
            };
            reader.readAsDataURL(file);
        }

        function renderUploadedImage(url) {
            if (!url) {
                imagePreview.textContent = 'لا توجد صورة مرفوعة حالياً.';
                return;
            }

            imagePreview.innerHTML = `<img src="${url}" alt="صورة البلاغ المرفوعة">`;
        }

        function setLocationPreview(latitude, longitude) {
            latitudeInput.value = latitude;
            longitudeInput.value = longitude;
            latitudeText.textContent = Number(latitude).toFixed(6);
            longitudeText.textContent = Number(longitude).toFixed(6);
        }

        function resetLocationPreview() {
            latitudeInput.value = '';
            longitudeInput.value = '';
            latitudeText.textContent = 'لم يتم تحديد الموقع بعد';
            longitudeText.textContent = 'لم يتم تحديد الموقع بعد';
        }

        imageInput.addEventListener('change', (event) => {
            const file = event.target.files?.[0] || null;
            renderSelectedImage(file);
        });

        function requireLocation() {
            if (latitudeInput.value && longitudeInput.value) {
                return true;
            }

            setStatus(reportStatus, 'warning', 'يرجى تحديد الموقع قبل إرسال البلاغ.');
            renderTimeline([
                {
                    title: 'الموقع غير محدد',
                    detail: 'يجب الحصول على الموقع الجغرافي لإرسال البلاغ بنجاح.',
                },
            ]);
            return false;
        }

        captureLocationBtn.addEventListener('click', () => {
            if (!navigator.geolocation) {
                setStatus(reportStatus, 'error', 'جهازك لا يدعم تحديد الموقع الجغرافي.');
                return;
            }

            captureLocationBtn.disabled = true;
            setStatus(reportStatus, 'info', 'جاري الحصول على الموقع...');

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const { latitude, longitude } = position.coords;
                    setLocationPreview(latitude, longitude);
                    setStatus(reportStatus, 'success', 'تم تحديد الموقع بنجاح.');
                    renderTimeline([
                        {
                            title: 'تم تحديد الموقع',
                            detail: `الموقع تم حفظه في ${nowLabel()}`,
                        },
                    ]);
                    captureLocationBtn.disabled = false;
                },
                (error) => {
                    let message = 'حدث خطأ أثناء محاولة الحصول على الموقع.';

                    if (error.code === error.PERMISSION_DENIED) {
                        message = 'يرجى السماح بالوصول إلى الموقع لتحديد مكان البلاغ.';
                    } else if (error.code === error.POSITION_UNAVAILABLE) {
                        message = 'الموقع غير متوفر حالياً. حاول مرة أخرى لاحقاً.';
                    } else if (error.code === error.TIMEOUT) {
                        message = 'انتهت مهلة الحصول على الموقع. حاول مرة أخرى.';
                    }

                    setStatus(reportStatus, 'error', message);
                    captureLocationBtn.disabled = false;
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0,
                }
            );
        });

        reportForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (!reportForm.reportValidity()) {
                setStatus(reportStatus, 'warning', 'يرجى تعبئة جميع الحقول المطلوبة بشكل صحيح.');
                return;
            }

            if (!requireLocation()) {
                return;
            }

            submitReportBtn.disabled = true;
            setStatus(reportStatus, 'info', 'جاري إرسال البلاغ...');
            renderTimeline([
                {
                    title: 'جاري إرسال البلاغ',
                    detail: `يتم معالجة البلاغ الآن (${nowLabel()}).`,
                },
            ]);

            const formData = new FormData(reportForm);

            try {
                const response = await fetch('/api/citizen/reports', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: formData,
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const firstValidationError = payload.errors
                        ? Object.values(payload.errors).flat()[0]
                        : null;

                    throw new Error(firstValidationError || payload.message || 'حدث خطأ أثناء إرسال البلاغ.');
                }

                const report = payload.data || {};
                const assignedOfficer = report.assigned_officer || {};
                const officerName = assignedOfficer.name || report.latest_assignment?.officer?.name || 'غير محدد';
                const cityName = report.location?.city || 'غير محدد';
                const reportStatusLabel = getReportStatusLabel(report.status);

                setStatus(reportStatus, 'success', 'تم إرسال البلاغ بنجاح وسيتم معالجته قريباً.');
                renderTimeline([
                    {
                        title: 'تم إرسال البلاغ',
                        detail: `رقم البلاغ: ${report.id || '-'} (${nowLabel()}).`,
                    },
                    {
                        title: 'حالة البلاغ',
                        detail: `الحالة: ${reportStatusLabel}. المدينة: ${cityName}. الضابط المسؤول: ${officerName}.`,
                    },
                ]);

                reportForm.reset();
                resetLocationPreview();
                renderUploadedImage(report.image_url || null);
            } catch (error) {
                setStatus(reportStatus, 'error', error.message);
                renderTimeline([
                    {
                        title: 'فشل إرسال البلاغ',
                        detail: error.message,
                    },
                ]);
            } finally {
                submitReportBtn.disabled = false;
            }
        });

        function getViolationTypeName(violation) {
            return violation.violation_type?.name || violation.violation_type_name || 'غير معروف';
        }

        function getViolationLocation(violation) {
            const location = violation.violation_location || {};
            const city = location.city_record?.name || location.city || '';
            const street = location.street_name || '';
            const landmark = location.landmark || '';
            return [city, street, landmark].filter(Boolean).join(' - ') || 'غير متوفر';
        }

        function getOccurredAt(violation) {
            return violation.occurred_at || violation.created_at || 'غير متوفرة';
        }

        function renderSearchResults(rows) {
            searchResults.innerHTML = '';

            if (!rows.length) {
                searchResults.innerHTML = '<div class="empty-state">لا توجد نتائج مطابقة. تأكد من رقم اللوحة وحاول مرة أخرى.</div>';
                return;
            }

            rows.forEach((violation) => {
                const card = document.createElement('article');
                card.className = 'result-card';

                const typeName = getViolationTypeName(violation);
                const fineAmount = violation.fine_amount ?? violation.violation_type?.fine_amount ?? '-';
                const plateNumber = violation.vehicle?.plate_number || violation.plate_number || 'غير متوفر';
                const locationText = getViolationLocation(violation);
                const description = violation.description || violation.violation_type?.description || 'لا يوجد وصف متوفر.';
                const appealStatus = violation.appeal?.status;
                const appealNote = appealStatus ? `حالة الاستئناف: ${appealStatus}.` : 'لا يوجد استئناف مسجّل.';

                card.innerHTML = `
                    <h3>${typeName}</h3>
                    <div class="result-meta">
                        <div><strong>رقم اللوحة</strong><span>${plateNumber}</span></div>
                        <div><strong>المبلغ المستحق</strong><span>${fineAmount}</span></div>
                        <div><strong>الموقع</strong><span>${locationText}</span></div>
                        <div><strong>تاريخ المخالفة</strong><span>${getOccurredAt(violation)}</span></div>
                    </div>
                    <p>${description}</p>
                    <p style="margin-top: 10px;">${appealNote}</p>
                    <div class="actions" style="margin-top: 14px;">
                        <button class="btn btn-outline" type="button" data-violation-id="${violation.id}">استئناف المخالفة</button>
                    </div>
                `;

                const button = card.querySelector('button');
                button.addEventListener('click', () => {
                    localStorage.setItem('violation_id', String(violation.id));
                    localStorage.setItem('selected_violation', JSON.stringify(violation));
                    window.location.href = '/citizen/appeal-form';
                });

                searchResults.appendChild(card);
            });
        }

        searchForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (!searchForm.reportValidity()) {
                setStatus(searchStatus, 'warning', 'يرجى إدخال رقم اللوحة بشكل صحيح.');
                return;
            }

            const plate = document.getElementById('plateNumber').value.trim();
            searchBtn.disabled = true;
            setStatus(searchStatus, 'info', 'جاري البحث عن البلاغ...');
            searchResults.innerHTML = '';

            try {
                const response = await fetch(`{{ route('citizen.violations') }}?plate=${encodeURIComponent(plate)}`, {
                    headers: { 'Accept': 'application/json' },
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(payload.message || 'حدث خطأ أثناء البحث.');
                }

                renderSearchResults(payload.data || []);
            } catch (error) {
                setStatus(searchStatus, 'error', error.message);
            } finally {
                searchBtn.disabled = false;
            }
        });
    </script>
</body>
</html>

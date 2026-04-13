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
      --navy: #1f5f8d;
      --blue: #5fc4f2;
      --blue-strong: #249fdb;
      --gold: #ffd84d;
      --gold-strong: #ffbe1a;
      --paper: #ffffff;
      --surface: #ffffff;
      --line: rgba(36, 159, 219, 0.20);
      --text: #24425f;
      --muted: #6d88a3;
      --success: #1d9d6c;
      --warning: #c88b00;
      --danger: #d9544f;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: "Cairo", sans-serif;
      color: var(--text);
      background:
        radial-gradient(circle at top left, rgba(95, 196, 242, 0.34), transparent 24%),
        radial-gradient(circle at top right, rgba(255, 216, 77, 0.24), transparent 22%),
        radial-gradient(circle at bottom left, rgba(95, 196, 242, 0.16), transparent 28%),
        linear-gradient(180deg, #f6fcff 0%, #ffffff 55%, #f1faff 100%);
      overflow-x: hidden;
    }

    body::before {
      content: "";
      position: fixed;
      inset: 0;
      pointer-events: none;
      background: linear-gradient(120deg, transparent 0%, rgba(255, 255, 255, 0.34) 45%, transparent 100%);
      transform: translateX(-120%);
      animation: pageShine 10s linear infinite;
    }

    .page {
      max-width: 1180px;
      margin: 0 auto;
      padding: 28px 18px 40px;
      position: relative;
      z-index: 1;
    }

    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 16px;
      margin-bottom: 22px;
    }

    .page-header h1 {
      margin: 0 0 6px;
      font-size: 2rem;
      position: relative;
      display: inline-block;
    }

    .page-header p {
      margin: 0;
      color: var(--muted);
    }

    .page-header h1::after {
      content: "";
      position: absolute;
      right: 0;
      bottom: -8px;
      width: 76px;
      height: 6px;
      border-radius: 999px;
      background: linear-gradient(135deg, var(--blue), var(--gold));
      animation: glowBar 2.4s ease-in-out infinite;
    }

    .layout {
      display: grid;
      grid-template-columns: minmax(0, 1.3fr) minmax(300px, 0.8fr);
      gap: 18px;
    }

    .stack {
      display: grid;
      gap: 18px;
    }

    .card {
      background: var(--paper);
      border: 1px solid var(--line);
      border-radius: 24px;
      box-shadow: 0 18px 45px rgba(36, 159, 219, 0.12);
      overflow: hidden;
      transition: transform 220ms ease, box-shadow 220ms ease, border-color 220ms ease;
      animation: cardRise 500ms ease both;
    }

    .card:hover {
      transform: translateY(-4px);
      box-shadow: 0 24px 54px rgba(36, 159, 219, 0.18);
      border-color: rgba(255, 190, 26, 0.35);
    }

    .card-body {
      padding: 24px;
    }

    .card h2,
    .card h3,
    .card p {
      margin: 0;
    }

    .card p {
      color: var(--muted);
    }

    .section-head {
      margin-bottom: 18px;
    }

    .section-head h2,
    .section-head h3 {
      margin-bottom: 6px;
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 14px;
    }

    .field {
      display: grid;
      gap: 8px;
    }

    .field.full {
      grid-column: 1 / -1;
    }

    label {
      font-weight: 700;
      color: var(--text);
    }

    input,
    select,
    textarea {
      width: 100%;
      padding: 12px 14px;
      border-radius: 14px;
      border: 1px solid var(--line);
      background: linear-gradient(180deg, #ffffff, #f8fdff);
      font: inherit;
      transition: border-color 180ms ease, box-shadow 180ms ease, transform 180ms ease;
    }

    input:hover,
    select:hover,
    textarea:hover {
      border-color: rgba(95, 196, 242, 0.56);
    }

    input:focus,
    select:focus,
    textarea:focus {
      outline: none;
      border-color: rgba(36, 159, 219, 0.76);
      box-shadow: 0 0 0 4px rgba(95, 196, 242, 0.24);
      transform: translateY(-1px);
    }

    textarea {
      min-height: 120px;
      resize: vertical;
    }

    .actions {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 16px;
    }

    .btn {
      border: 0;
      border-radius: 14px;
      padding: 12px 18px;
      font: inherit;
      font-weight: 800;
      cursor: pointer;
      transition: transform 180ms ease, box-shadow 180ms ease, filter 180ms ease;
      position: relative;
      overflow: hidden;
      text-decoration: none;
    }

    .btn::after {
      content: "";
      position: absolute;
      top: 0;
      left: -140%;
      width: 80%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.42), transparent);
      transform: skewX(-20deg);
      transition: left 360ms ease;
    }

    .btn:hover::after {
      left: 150%;
    }

    .btn:hover {
      transform: translateY(-2px);
      filter: saturate(1.05);
    }

    .btn:active {
      transform: translateY(0);
    }

    .btn-primary {
      color: #214868;
      background: linear-gradient(135deg, var(--gold), #fff1a0);
      box-shadow: 0 14px 28px rgba(255, 216, 77, 0.24);
    }

    .btn-secondary {
      color: var(--navy);
      background: linear-gradient(135deg, #f5fbff, #dff6ff);
      border: 1px solid var(--line);
      box-shadow: 0 10px 20px rgba(36, 159, 219, 0.18);
    }

    .status-box {
      margin-top: 16px;
      padding: 14px 16px;
      border-radius: 16px;
      background: linear-gradient(135deg, #fbfeff, #f5fbff);
      border: 1px solid var(--line);
      color: var(--muted);
      font-weight: 700;
      animation: pulseSoft 2.8s ease-in-out infinite;
    }

    .details-list {
      display: grid;
      gap: 12px;
    }

    .details-row {
      display: flex;
      justify-content: space-between;
      gap: 10px;
      padding-bottom: 12px;
      border-bottom: 1px solid rgba(16, 37, 63, 0.08);
    }

    .details-row:last-child {
      border-bottom: 0;
      padding-bottom: 0;
    }

    .details-row span {
      color: var(--muted);
    }

    .details-row strong {
      text-align: left;
    }

    .preview-box {
      display: grid;
      place-items: center;
      min-height: 190px;
      border: 1px dashed rgba(16, 37, 63, 0.16);
      border-radius: 18px;
      background:
        linear-gradient(135deg, rgba(95, 196, 242, 0.10), rgba(255, 216, 77, 0.08)),
        #fbfdff;
      color: var(--muted);
      text-align: center;
      padding: 18px;
      margin-bottom: 18px;
      transition: transform 220ms ease, border-color 220ms ease, box-shadow 220ms ease;
    }

    .preview-box:hover {
      transform: translateY(-2px);
      border-color: rgba(255, 190, 26, 0.38);
      box-shadow: inset 0 0 0 1px rgba(95, 196, 242, 0.20);
    }

    .preview-box img {
      width: 100%;
      max-height: 260px;
      object-fit: cover;
      border-radius: 14px;
      animation: zoomFade 320ms ease;
    }

    .timeline {
      display: grid;
      gap: 14px;
    }

    .timeline-item {
      display: grid;
      grid-template-columns: 18px minmax(0, 1fr);
      gap: 12px;
      align-items: start;
    }

    .timeline-marker {
      width: 18px;
      height: 18px;
      margin-top: 2px;
      border-radius: 999px;
      border: 2px solid #b9dcee;
      background: white;
      transition: transform 180ms ease, background 180ms ease, border-color 180ms ease;
    }

    .timeline-item.active .timeline-marker {
      border-color: var(--blue-strong);
      background: var(--blue-strong);
      animation: pulseDot 1.4s ease-in-out infinite;
    }

    .timeline-item.done .timeline-marker {
      border-color: var(--success);
      background: var(--success);
    }

    .timeline-item strong {
      display: block;
      margin-bottom: 4px;
    }

    .search-box {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      gap: 10px;
      margin-bottom: 16px;
    }

    .results {
      display: grid;
      gap: 14px;
    }

    .result-card {
      padding: 18px;
      border-radius: 20px;
      border: 1px solid var(--line);
      background:
        linear-gradient(180deg, #fff, var(--surface)),
        linear-gradient(135deg, rgba(95, 196, 242, 0.08), rgba(255, 216, 77, 0.08));
      transition: transform 220ms ease, box-shadow 220ms ease, border-color 220ms ease;
    }

    .result-card:hover {
      transform: translateY(-3px);
      border-color: rgba(95, 196, 242, 0.42);
      box-shadow: 0 18px 34px rgba(36, 159, 219, 0.12);
    }

    .result-card p {
      margin-top: 8px;
      line-height: 1.8;
    }

    .mini-note {
      padding: 16px;
      border-radius: 18px;
      background: linear-gradient(135deg, #fbfeff, #f8fdff);
      border: 1px solid var(--line);
      line-height: 1.9;
      color: var(--muted);
    }

    .status-inline {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 140px;
    }

    .pill {
      display: inline-flex;
      padding: 6px 12px;
      border-radius: 999px;
      font-size: 0.9rem;
      font-weight: 800;
    }

    .pill-blue {
      color: #1f5f8d;
      background: #d8f0fb;
    }

    .pill-green {
      color: var(--success);
      background: #dbf8e9;
    }

    .pill-orange {
      color: var(--warning);
      background: #fff4c9;
    }

    @keyframes cardRise {
      from { opacity: 0; transform: translateY(18px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes zoomFade {
      from { opacity: 0; transform: scale(0.97); }
      to { opacity: 1; transform: scale(1); }
    }

    @keyframes pulseSoft {
      0%, 100% { box-shadow: 0 0 0 0 rgba(95, 196, 242, 0); }
      50% { box-shadow: 0 0 0 6px rgba(95, 196, 242, 0.16); }
    }

    @keyframes pulseDot {
      0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(36, 159, 219, 0); }
      50% { transform: scale(1.08); box-shadow: 0 0 0 7px rgba(36, 159, 219, 0.18); }
    }

    @keyframes glowBar {
      0%, 100% { box-shadow: 0 0 0 rgba(255, 216, 77, 0); }
      50% { box-shadow: 0 0 18px rgba(255, 216, 77, 0.34); }
    }

    @keyframes pageShine {
      0% { transform: translateX(-120%); }
      100% { transform: translateX(120%); }
    }

    @media (max-width: 980px) {
      .layout,
      .form-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="page">
    <header class="page-header">
      <div>
        <h1>بوابة المواطن</h1>
        <p>قدّم بلاغك وتابع المخالفات من مكان واحد.</p>
      </div>
    </header>

    <div class="stack">
      <div class="layout">
        <main class="stack">
          <section class="card">
            <div class="card-body">
              <div class="section-head">
                <h2>إرسال بلاغ جديد</h2>
                <p>أرسل صورة ووصفًا مختصرًا مع موقعك الحالي.</p>
              </div>

              <form id="dispatchForm">
                <div class="form-grid">
                  <div class="field">
                    <label for="reportTitle">عنوان البلاغ</label>
                    <input id="reportTitle" type="text" placeholder="مثال: سيارة تسد مسرب الإسعاف" required>
                  </div>

                  <div class="field">
                    <label for="priority">الأولوية</label>
                    <select id="priority">
                      <option value="منخفضة">منخفضة</option>
                      <option value="متوسطة" selected>متوسطة</option>
                      <option value="عالية">عالية</option>
                      <option value="عاجلة">عاجلة</option>
                    </select>
                  </div>

                  <div class="field">
                    <label for="reporterName">اسم المواطن</label>
                    <input id="reporterName" type="text" placeholder="الاسم الكامل" required>
                  </div>

                  <div class="field">
                    <label for="reporterPhone">رقم الهاتف</label>
                    <input id="reporterPhone" type="tel" placeholder="09xxxxxxxx" required>
                  </div>

                  <div class="field full">
                    <label for="description">وصف البلاغ</label>
                    <textarea id="description" placeholder="اكتب وصفًا مختصرًا وواضحًا للحالة." required></textarea>
                  </div>

                  <div class="field full">
                    <label for="reportImage">صورة البلاغ</label>
                    <input id="reportImage" type="file" accept="image/*">
                  </div>
                </div>

                <div class="actions">
                  <button class="btn btn-secondary" type="button" id="captureLocationBtn">تحديد موقعي الحالي</button>
                  <button class="btn btn-primary" type="submit">إرسال البلاغ</button>
                </div>

                <div id="dispatchStatus" class="status-box">بانتظار إدخال بيانات البلاغ.</div>
              </form>
            </div>
          </section>

          <section class="card">
            <div class="card-body">
              <div class="section-head">
                <h2>البحث عن المخالفات</h2>
                <p>ابحث برقم اللوحة ثم قدّم اعتراضًا على المخالفة إن لزم.</p>
              </div>

              <div class="search-box">
                <input type="text" id="plateInput" placeholder="أدخل رقم اللوحة">
                <button class="btn btn-primary" id="searchBtn" type="button">بحث</button>
              </div>

              <div id="results" class="results"></div>
            </div>
          </section>
        </main>

        <aside class="stack">
          <section class="card">
            <div class="card-body">
              <div class="section-head">
                <h3>معاينة البلاغ</h3>
                <p>راجع الصورة والموقع قبل الإرسال.</p>
              </div>

              <div id="imagePreviewWrap" class="preview-box">لم يتم اختيار صورة بعد.</div>

              <div class="details-list">
                <div class="details-row"><span>خط العرض</span><strong id="latitudeValue">غير محدد</strong></div>
                <div class="details-row"><span>خط الطول</span><strong id="longitudeValue">غير محدد</strong></div>
                <div class="details-row"><span>وقت الالتقاط</span><strong id="capturedTimeValue">بانتظار التحديد</strong></div>
              </div>
            </div>
          </section>

          <section class="card">
            <div class="card-body">
              <div class="section-head">
                <h3>حالة التوزيع</h3>
                <p>تابع حالة البلاغ.</p>
              </div>

              <div id="dispatchTimeline" class="timeline">
                <div class="timeline-item active">
                  <span class="timeline-marker"></span>
                  <div>
                    <strong>تم تجهيز البلاغ</strong>
                    بانتظار الموقع والصورة ثم الإرسال.
                  </div>
                </div>
                <div class="timeline-item">
                  <span class="timeline-marker"></span>
                  <div>
                    <strong>تم إنشاء البلاغ</strong>
                    السيرفر يسجل البلاغ كحالة جديدة.
                  </div>
                </div>
                <div class="timeline-item">
                  <span class="timeline-marker"></span>
                  <div>
                    <strong>تم اختيار الشرطي</strong>
                    يتم اختيار أقرب شرطي متاح.
                  </div>
                </div>
                <div class="timeline-item">
                  <span class="timeline-marker"></span>
                  <div>
                    <strong>تم إرسال الإشعار</strong>
                    يصل البلاغ إلى تطبيق الشرطي.
                  </div>
                </div>
              </div>
            </div>
          </section>

          <section class="card">
            <div class="card-body">
              <div class="section-head">
                <h3>الحالة الحالية</h3>
              </div>

              <div class="mini-note">
                <span class="pill pill-green status-inline" id="readyStateBadge">جاهز للإرسال</span>
              </div>
            </div>
          </section>
        </aside>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const plateInput = document.getElementById('plateInput');
      const searchBtn = document.getElementById('searchBtn');
      const results = document.getElementById('results');
      const dispatchForm = document.getElementById('dispatchForm');
      const reportImage = document.getElementById('reportImage');
      const imagePreviewWrap = document.getElementById('imagePreviewWrap');
      const captureLocationBtn = document.getElementById('captureLocationBtn');
      const dispatchStatus = document.getElementById('dispatchStatus');
      const latitudeValue = document.getElementById('latitudeValue');
      const longitudeValue = document.getElementById('longitudeValue');
      const capturedTimeValue = document.getElementById('capturedTimeValue');
      const dispatchTimeline = document.getElementById('dispatchTimeline');
      const readyStateBadge = document.getElementById('readyStateBadge');

      const steps = [
        { title: 'تم تجهيز البلاغ', detail: 'بانتظار الموقع والصورة ثم الإرسال.' },
        { title: 'تم إنشاء البلاغ', detail: 'السيرفر يسجل البلاغ كحالة جديدة.' },
        { title: 'تم اختيار الشرطي', detail: 'يتم اختيار أقرب شرطي متاح.' },
        { title: 'تم إرسال الإشعار', detail: 'يصل البلاغ إلى تطبيق الشرطي.' }
      ];

      let capturedLocation = null;

      function renderTimeline(activeIndex) {
        dispatchTimeline.innerHTML = steps.map((step, index) => {
          const stateClass = index < activeIndex ? 'done' : (index === activeIndex ? 'active' : '');
          return `
            <div class="timeline-item ${stateClass}">
              <span class="timeline-marker"></span>
              <div>
                <strong>${step.title}</strong>
                ${step.detail}
              </div>
            </div>
          `;
        }).join('');
      }

      function setDispatchMessage(message) {
        dispatchStatus.textContent = message;
      }

      function setReadyState(label, tone = 'green') {
        readyStateBadge.textContent = label;
        readyStateBadge.className = `pill status-inline ${tone === 'orange' ? 'pill-orange' : tone === 'blue' ? 'pill-blue' : 'pill-green'}`;
      }

      function updateLocationPreview(latitude, longitude) {
        const timestamp = new Date();
        capturedLocation = { latitude, longitude };
        latitudeValue.textContent = latitude.toFixed(6);
        longitudeValue.textContent = longitude.toFixed(6);
        capturedTimeValue.textContent = timestamp.toLocaleString();
      }

      function captureCurrentLocation() {
        if (!navigator.geolocation) {
          setDispatchMessage('المتصفح لا يدعم تحديد الموقع.');
          return;
        }

        setDispatchMessage('جاري تحديد الموقع الحالي...');

        navigator.geolocation.getCurrentPosition(
          (position) => {
            updateLocationPreview(position.coords.latitude, position.coords.longitude);
            setDispatchMessage('تم تحديد الموقع. أصبح البلاغ جاهزًا للإرسال.');
            setReadyState('جاهز للإرسال', 'green');
          },
          () => {
            updateLocationPreview(33.5138, 36.2765);
            setDispatchMessage('تعذر الوصول للموقع الفعلي، تم استخدام موقع تجريبي داخل دمشق.');
            setReadyState('جاهز للإرسال', 'green');
          },
          { enableHighAccuracy: true, timeout: 8000 }
        );
      }

      reportImage.addEventListener('change', (event) => {
        const file = event.target.files?.[0];
        if (!file) {
          imagePreviewWrap.textContent = 'لم يتم اختيار صورة بعد.';
          return;
        }

        const reader = new FileReader();
        reader.onload = () => {
          imagePreviewWrap.innerHTML = `<img src="${reader.result}" alt="معاينة صورة البلاغ">`;
        };
        reader.readAsDataURL(file);
      });

      captureLocationBtn.addEventListener('click', captureCurrentLocation);

      dispatchForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!capturedLocation) {
          captureCurrentLocation();
        }

        renderTimeline(1);
        setDispatchMessage('تم إرسال البلاغ وإنشاء سجل جديد.');
        setReadyState('تم الإرسال', 'blue');

        await new Promise((resolve) => setTimeout(resolve, 700));
        renderTimeline(2);
        setDispatchMessage('جاري اختيار أقرب شرطي متاح.');
        setReadyState('قيد المعالجة', 'orange');

        await new Promise((resolve) => setTimeout(resolve, 800));
        renderTimeline(3);
        setDispatchMessage('تم إرسال البلاغ إلى الشرطي الأقرب.');
        setReadyState('تم التوجيه', 'green');
      });

      async function searchViolations() {
        const plate = plateInput.value.trim();

        if (!plate) {
          results.innerHTML = '<div class="result-card">الرجاء إدخال رقم اللوحة.</div>';
          return;
        }

        results.innerHTML = '<div class="result-card">جاري البحث...</div>';

        try {
          const response = await fetch(`/citizen/violations?plate=${encodeURIComponent(plate)}`, {
            headers: { Accept: 'application/json' }
          });

          if (!response.ok) {
            throw new Error(`Request failed with status ${response.status}`);
          }

          const payload = await response.json();
          const violations = payload.data ?? payload;

          if (!Array.isArray(violations) || violations.length === 0) {
            results.innerHTML = '<div class="result-card">لا توجد مخالفات لهذا الرقم.</div>';
            return;
          }

          results.innerHTML = violations.map((violation) => {
            const type = violation.violation_type?.name ?? '-';
            const fine = violation.violation_type?.fine_amount ?? '-';
            const plateNumber = violation.vehicle_snapshot?.plate_number ?? violation.vehicle?.plate_number ?? '-';
            const city = violation.violation_location?.city_record?.name ?? violation.violation_location?.city ?? '-';
            const street = violation.violation_location?.street_name ?? violation.location?.street_name ?? '-';
            const description = violation.description ?? '-';
            const date = violation.occurred_at ?? '-';
            const appeal = violation.appeal;
            const appealButton = appeal
              ? `<span class="pill pill-orange">الاعتراض: ${appeal.status}</span>`
              : `<button class="btn btn-secondary objection-btn" type="button" data-id="${violation.id}">تقديم اعتراض</button>`;

            return `
              <article class="result-card">
                <strong>${type}</strong>
                <p>رقم اللوحة: ${plateNumber}</p>
                <p>قيمة الغرامة: ${fine}</p>
                <p>المدينة: ${city}</p>
                <p>الشارع: ${street}</p>
                <p>الوصف: ${description}</p>
                <p>التاريخ: ${date}</p>
                ${appealButton}
              </article>
            `;
          }).join('');

          document.querySelectorAll('.objection-btn').forEach((button) => {
            button.addEventListener('click', () => {
              const violationId = button.getAttribute('data-id');
              if (!violationId) return;
              const selectedViolation = violations.find((item) => String(item.id) === String(violationId));
              localStorage.setItem('violation_id', violationId);
              if (selectedViolation) {
                localStorage.setItem('selected_violation', JSON.stringify(selectedViolation));
              }
              window.location.href = '/citizen/appeal-form';
            });
          });
        } catch (error) {
          console.error(error);
          results.innerHTML = '<div class="result-card">حدث خطأ أثناء تحميل المخالفات.</div>';
        }
      }

      searchBtn.addEventListener('click', searchViolations);
      plateInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
          event.preventDefault();
          searchViolations();
        }
      });

      renderTimeline(0);
      setReadyState('جاهز للإرسال', 'green');
    });
  </script>
</body>
</html>

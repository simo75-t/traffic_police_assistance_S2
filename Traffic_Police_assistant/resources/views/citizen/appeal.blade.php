<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>تقديم اعتراض</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-top: #dbe7f1;
      --bg-mid: #cddbe7;
      --bg-bottom: #bfcfdd;
      --navy: #1f4f74;
      --navy-strong: #153955;
      --blue: #3a81b3;
      --gold: #d8b04c;
      --gold-soft: #ead392;
      --surface: rgba(248, 251, 254, 0.92);
      --surface-strong: rgba(239, 246, 251, 0.96);
      --border: rgba(21, 57, 85, 0.12);
      --text: #183247;
      --muted: #5f7487;
      --success: #16724f;
      --danger: #b63b35;
      --shadow: 0 24px 60px rgba(21, 57, 85, 0.14);
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      min-height: 100vh;
      font-family: "Cairo", sans-serif;
      color: var(--text);
      background:
        radial-gradient(circle at top right, rgba(216, 176, 76, 0.16), transparent 22%),
        radial-gradient(circle at top left, rgba(58, 129, 179, 0.12), transparent 24%),
        linear-gradient(180deg, var(--bg-top) 0%, var(--bg-mid) 48%, var(--bg-bottom) 100%);
    }

    .page {
      max-width: 1120px;
      margin: 0 auto;
      padding: 34px 20px 44px;
    }

    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 16px;
      margin-bottom: 24px;
    }

    .page-header h1 {
      margin: 0 0 8px;
      font-size: 2rem;
      color: var(--navy-strong);
    }

    .page-header p {
      margin: 0;
      color: var(--muted);
      font-size: 1rem;
    }

    .back-link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 12px 18px;
      border-radius: 14px;
      background: linear-gradient(135deg, rgba(248, 251, 254, 0.94), rgba(233, 242, 249, 0.94));
      border: 1px solid var(--border);
      color: var(--navy-strong);
      text-decoration: none;
      font-weight: 800;
      box-shadow: 0 12px 28px rgba(21, 57, 85, 0.08);
      transition: transform 180ms ease, box-shadow 180ms ease;
    }

    .back-link:hover {
      transform: translateY(-2px);
      box-shadow: 0 16px 34px rgba(21, 57, 85, 0.12);
    }

    .layout {
      display: grid;
      grid-template-columns: minmax(320px, 0.9fr) minmax(0, 1.1fr);
      gap: 20px;
      align-items: start;
    }

    .panel {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 24px;
      box-shadow: var(--shadow);
      overflow: hidden;
      backdrop-filter: blur(10px);
    }

    .panel-body {
      padding: 26px;
    }

    .panel h2,
    .panel h3,
    .panel p {
      margin: 0;
    }

    .panel p {
      color: var(--muted);
    }

    .section-head {
      margin-bottom: 18px;
    }

    .section-head h2,
    .section-head h3 {
      margin-bottom: 6px;
      color: var(--navy-strong);
    }

    .details-card {
      padding: 18px;
      border-radius: 18px;
      background: linear-gradient(180deg, rgba(255,255,255,0.76), rgba(238, 246, 251, 0.88));
      border: 1px solid rgba(58, 129, 179, 0.14);
      min-height: 220px;
    }

    .details-card p {
      line-height: 1.8;
      margin-top: 8px;
    }

    form {
      display: grid;
      gap: 16px;
    }

    label {
      display: block;
      margin-bottom: 8px;
      color: var(--navy-strong);
      font-weight: 800;
    }

    textarea {
      width: 100%;
      min-height: 220px;
      padding: 16px 18px;
      border-radius: 18px;
      border: 1px solid rgba(58, 129, 179, 0.18);
      background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(245, 250, 253, 0.96));
      color: var(--text);
      font: inherit;
      resize: vertical;
      outline: none;
      transition: border-color 180ms ease, box-shadow 180ms ease, transform 180ms ease;
    }

    textarea:hover {
      border-color: rgba(58, 129, 179, 0.30);
    }

    textarea:focus {
      border-color: rgba(58, 129, 179, 0.64);
      box-shadow: 0 0 0 4px rgba(58, 129, 179, 0.16);
      transform: translateY(-1px);
    }

    .actions {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
    }

    .btn {
      appearance: none;
      border: 0;
      border-radius: 16px;
      padding: 13px 22px;
      font: inherit;
      font-weight: 800;
      cursor: pointer;
      transition: transform 180ms ease, box-shadow 180ms ease, filter 180ms ease;
    }

    .btn-primary {
      color: #183247;
      background: linear-gradient(135deg, var(--gold), var(--gold-soft));
      box-shadow: 0 14px 28px rgba(216, 176, 76, 0.24);
    }

    .btn-secondary {
      color: var(--navy-strong);
      background: linear-gradient(135deg, rgba(248, 251, 254, 0.94), rgba(227, 238, 246, 0.94));
      border: 1px solid var(--border);
      text-decoration: none;
    }

    .btn:hover {
      transform: translateY(-2px);
      filter: saturate(1.03);
    }

    .btn:active {
      transform: translateY(0);
    }

    .response-box {
      min-height: 56px;
      padding: 14px 16px;
      border-radius: 16px;
      background: rgba(255, 255, 255, 0.46);
      border: 1px solid rgba(21, 57, 85, 0.08);
      display: flex;
      align-items: center;
      color: var(--muted);
      font-weight: 700;
    }

    .response-box p {
      margin: 0;
      font-weight: 800;
    }

    .response-box p[style*="lightgreen"],
    .response-box p[style*="green"] {
      color: var(--success) !important;
    }

    .response-box p[style*="red"] {
      color: var(--danger) !important;
    }

    .status-bar {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 10px 14px;
      border-radius: 999px;
      background: rgba(58, 129, 179, 0.10);
      color: var(--navy-strong);
      font-weight: 800;
    }

    .status-dot {
      width: 10px;
      height: 10px;
      border-radius: 999px;
      background: var(--gold);
      box-shadow: 0 0 0 8px rgba(216, 176, 76, 0.14);
    }

    @media (max-width: 920px) {
      .layout {
        grid-template-columns: 1fr;
      }

      .page-header {
        flex-direction: column;
        align-items: stretch;
      }
    }
  </style>
</head>
<body>
  <div class="page">
    <header class="page-header">
      <div>
        <h1>تقديم اعتراض</h1>
        <p>راجع بيانات المخالفة ثم اكتب سبب الاعتراض بشكل واضح ومباشر.</p>
      </div>
      <a class="back-link" href="{{ url('/') }}">العودة إلى بوابة المواطن</a>
    </header>

    <div class="layout">
      <section class="panel">
        <div class="panel-body">
          <div class="section-head">
            <h2>بيانات المخالفة</h2>
            <p>المعلومات المرتبطة بالمخالفة التي سيتم الاعتراض عليها.</p>
          </div>

          <div class="status-bar">
            <span class="status-dot"></span>
            بانتظار تحميل تفاصيل المخالفة
          </div>

          <div id="violationDetails" class="details-card" style="margin-top: 18px;"></div>
        </div>
      </section>

      <section class="panel">
        <div class="panel-body">
          <div class="section-head">
            <h2>سبب الاعتراض</h2>
            <p>اكتب السبب الذي تريد تقديمه مع الاعتراض.</p>
          </div>

          <form id="AppealForm">
            <input type="hidden" id="violation_id" name="violation_id">

            <div>
              <label for="reason">نص الاعتراض</label>
              <textarea id="reason" name="reason" required placeholder="مثال: أريد الاعتراض لأن المخالفة لا تعود للمركبة الخاصة بي أو لأن البيانات المسجلة غير صحيحة."></textarea>
            </div>

            <div class="actions">
              <button class="btn btn-primary" type="submit">إرسال الاعتراض</button>
              <a class="btn btn-secondary" href="{{ url('/') }}">إلغاء</a>
            </div>

            <div id="responseMessage" class="response-box">لم يتم إرسال الاعتراض بعد.</div>
          </form>
        </div>
      </section>
    </div>
  </div>

  <script src="{{ asset('citizen/script.js') }}"></script>
</body>
</html>

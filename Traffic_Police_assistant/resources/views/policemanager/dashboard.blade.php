@extends('policemanager.layouts.app')

@section('title', 'لوحة تحكم مدير الشرطة')
@section('page_title', 'لوحة التحكم')
@section('page_description', 'نقطة البداية لمتابعة عمل مدير الشرطة بعد تسجيل الدخول.')

@section('content')
    <section class="stack">
        <div class="stats-grid">
            <article class="stat-card">
                <small>إجمالي المخالفات</small>
                <strong>{{ $stats['violationsCount'] }}</strong>
            </article>

            <article class="stat-card">
                <small>إجمالي الاعتراضات</small>
                <strong>{{ $stats['appealsCount'] }}</strong>
            </article>

            <article class="stat-card">
                <small>الاعتراضات قيد المراجعة</small>
                <strong>{{ $stats['pendingAppealsCount'] }}</strong>
            </article>
        </div>

        <div class="actions-grid">
            <article class="action-card">
                <h3>عرض المخالفات</h3>
                <p>الانتقال إلى شاشة المخالفات لمراجعة المخالفات المسجلة وتصفيتها حسب المبلّغ أو النوع أو المركبة أو وقت الحدوث.</p>
                <a class="btn btn-primary" href="{{ route('policemanager.violations.index') }}">فتح المخالفات</a>
            </article>

            <article class="action-card">
                <h3>عرض الخريطة الحرارية</h3>
                <p>تحليل كثافة المخالفات حسب الموقع والفترة الزمنية واستكشاف المناطق الأعلى ازدحاماً من شاشة واحدة.</p>
                <a class="btn btn-primary" href="{{ route('policemanager.violations.heatmap') }}">فتح الخريطة الحرارية</a>
            </article>

            <article class="action-card">
                <h3>خريطة البلاغات</h3>
                <p>متابعة بلاغات المواطنين على الخريطة ومعرفة حالة البلاغ وتوزيع العناصر المكلفة.</p>
                <a class="btn btn-primary" href="{{ route('policemanager.violations.map') }}">فتح خريطة البلاغات</a>
            </article>

            <article class="action-card">
                <h3>عرض الاعتراضات</h3>
                <p>مراجعة طلبات الاعتراض، الاطلاع على تفاصيلها، وتحديث حالة القرار من القائمة أو صفحة التفاصيل.</p>
                <a class="btn btn-primary" href="{{ route('policemanager.appeals.index') }}">فتح الاعتراضات</a>
            </article>

            <article class="action-card">
                <h3>تسجيل الخروج</h3>
                <p>إنهاء الجلسة الحالية بشكل آمن مع إعادة توليد رمز الحماية.</p>
                <form action="{{ route('policemanager.logout') }}" method="POST">
                    @csrf
                    <button class="btn btn-warning" type="submit">تسجيل الخروج</button>
                </form>
            </article>
        </div>
    </section>
@endsection
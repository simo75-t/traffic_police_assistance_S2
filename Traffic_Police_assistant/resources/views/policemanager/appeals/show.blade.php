@extends('policemanager.layouts.app')

@php
    use App\Enums\AppealStatus;

    $badgeClassMap = [
        AppealStatus::Pending => 'badge badge-pending',
        AppealStatus::Accepted => 'badge badge-accepted',
        AppealStatus::Rejected => 'badge badge-rejected',
    ];
    $statusLabels = AppealStatus::labels();
@endphp

@section('title', 'تفاصيل الاعتراض')
@section('page_title', 'تفاصيل الاعتراض')
@section('page_description', 'عرض تفاصيل اعتراض واحد وتحديث حالته النهائية من صفحة المراجعة.')

@section('content')
    <section class="stack">
        <div class="surface">
            <div class="surface-body">
                <div class="detail-grid">
                    <article class="detail-card">
                        <span class="detail-label">رقم المخالفة</span>
                        <div class="detail-value">{{ $appeal['violation_id'] }}</div>
                    </article>

                    <article class="detail-card">
                        <span class="detail-label">الحالة</span>
                        <div class="detail-value">
                            <span class="{{ $badgeClassMap[$appeal['status']] ?? 'badge badge-pending' }}">
                                {{ $statusLabels[$appeal['status']] ?? ucfirst($appeal['status']) }}
                            </span>
                        </div>
                    </article>

                    <article class="detail-card">
                        <span class="detail-label">السبب</span>
                        <div class="detail-value">{{ $appeal['reason'] }}</div>
                    </article>

                    <article class="detail-card">
                        <span class="detail-label">تاريخ الإنشاء</span>
                        <div class="detail-value">{{ $appeal['created_at'] }}</div>
                    </article>
                </div>
            </div>
        </div>

        <div class="surface">
            <div class="surface-body stack">
                <h3>تحديث الحالة</h3>

                <form class="inline-form" action="{{ route('policemanager.appeals.updateStatus', $appeal['id']) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <select name="status">
                        @foreach ($statusLabels as $value => $label)
                            <option value="{{ $value }}" @selected($appeal['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <button class="btn btn-primary" type="submit">تحديث الحالة</button>
                    <a class="btn btn-secondary" href="{{ route('policemanager.appeals.index') }}">العودة إلى القائمة</a>
                </form>

                @error('status')
                    <div class="flash flash-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </section>
@endsection
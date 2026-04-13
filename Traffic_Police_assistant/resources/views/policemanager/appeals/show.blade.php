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

@section('title', 'Appeal Details')
@section('page_title', 'Appeal Details')
@section('page_description', 'Inspect one appeal and update its final decision status from the detailed review page.')

@section('content')
    <section class="stack">
        <div class="surface">
            <div class="surface-body">
                <div class="detail-grid">
                    <article class="detail-card">
                        <span class="detail-label">Violation ID</span>
                        <div class="detail-value">{{ $appeal['violation_id'] }}</div>
                    </article>

                    <article class="detail-card">
                        <span class="detail-label">Status</span>
                        <div class="detail-value">
                            <span class="{{ $badgeClassMap[$appeal['status']] ?? 'badge badge-pending' }}">
                                {{ $statusLabels[$appeal['status']] ?? ucfirst($appeal['status']) }}
                            </span>
                        </div>
                    </article>

                    <article class="detail-card">
                        <span class="detail-label">Reason</span>
                        <div class="detail-value">{{ $appeal['reason'] }}</div>
                    </article>

                    <article class="detail-card">
                        <span class="detail-label">Created At</span>
                        <div class="detail-value">{{ $appeal['created_at'] }}</div>
                    </article>
                </div>
            </div>
        </div>

        <div class="surface">
            <div class="surface-body stack">
                <h3>Update Status</h3>

                <form class="inline-form" action="{{ route('policemanager.appeals.updateStatus', $appeal['id']) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <select name="status">
                        @foreach ($statusLabels as $value => $label)
                            <option value="{{ $value }}" @selected($appeal['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <button class="btn btn-primary" type="submit">Update Status</button>
                    <a class="btn btn-secondary" href="{{ route('policemanager.appeals.index') }}">Back to List</a>
                </form>

                @error('status')
                    <div class="flash flash-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </section>
@endsection

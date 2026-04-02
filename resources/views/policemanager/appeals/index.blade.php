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

@section('title', 'Appeals')
@section('page_title', 'Appeals')
@section('page_description', 'Browse all appeal requests and update their status directly from the list.')

@section('content')
    <section class="surface">
        <div class="surface-body">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Violation ID</th>
                            <th>Status</th>
                            <th>Reason</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($appeals as $appeal)
                            <tr>
                                <td>{{ $appeal['id'] }}</td>
                                <td>{{ $appeal['violation_id'] }}</td>
                                <td>
                                    <span class="{{ $badgeClassMap[$appeal['status']] ?? 'badge badge-pending' }}">
                                        {{ $statusLabels[$appeal['status']] ?? ucfirst($appeal['status']) }}
                                    </span>
                                </td>
                                <td>{{ $appeal['reason'] }}</td>
                                <td>{{ $appeal['created_at'] }}</td>
                                <td>
                                    <div class="stack">
                                        <a class="btn btn-secondary" href="{{ route('policemanager.appeals.show', $appeal['id']) }}">View</a>

                                        <form action="{{ route('policemanager.appeals.updateStatus', $appeal['id']) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="inline-form">
                                                <select name="status" onchange="this.form.submit()">
                                                    @foreach ($statusLabels as $value => $label)
                                                        <option value="{{ $value }}" @selected($appeal['status'] === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty-state">There are no appeals to review yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection

@extends('admin.layouts.app')

@section('title', 'Violation Types')

@section('content')
<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="page-title">Violation Types</h1>
        <p class="page-subtitle">Manage the catalog of supported violation types and their fine amounts.</p>
    </div>

    <a href="{{ route('admin.violationTypes.create') }}" class="btn text-white rounded-4 px-4 py-3 fw-bold" style="background: linear-gradient(135deg, #d7a93c, #a97817);">
        Add New Violation Type
    </a>
</div>

<div class="content-card">
    <div class="content-card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Fine Amount</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($violationTypes as $violation)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-bold">{{ $violation->name }}</td>
                            <td>{{ $violation->description ?: 'No description' }}</td>
                            <td>{{ number_format((float) $violation->fine_amount, 2) }}</td>
                            <td>{{ $violation->created_at?->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No violation types have been created yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

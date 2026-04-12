@extends('admin.layouts.app')

@section('title', 'Create Violation Type')

@section('content')
<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="page-title">Create Violation Type</h1>
        <p class="page-subtitle">Add a new violation type with a clear description and fine amount.</p>
    </div>
    <a href="{{ route('admin.violationTypes.index') }}" class="btn btn-light border rounded-4 px-4 py-3">Back to List</a>
</div>

<div class="content-card">
    <div class="content-card-body">
        @if ($errors->any())
            <div class="admin-alert admin-alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.violationTypes.store') }}" method="POST" class="row g-4">
            @csrf

            <div class="col-md-6">
                <label class="form-label fw-bold">Violation Name</label>
                <input type="text" name="name" class="form-control rounded-4" value="{{ old('name') }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Fine Amount</label>
                <input type="number" step="0.01" min="0" name="fine_amount" class="form-control rounded-4" value="{{ old('fine_amount') }}" required>
            </div>

            <div class="col-12">
                <label class="form-label fw-bold">Description</label>
                <textarea name="description" class="form-control rounded-4" rows="5">{{ old('description') }}</textarea>
            </div>

            <div class="col-12 d-flex gap-2 justify-content-end">
                <a href="{{ route('admin.violationTypes.index') }}" class="btn btn-light border rounded-4 px-4">Cancel</a>
                <button type="submit" class="btn text-white rounded-4 px-4" style="background: linear-gradient(135deg, #1a5d87, #10243d);">Save Violation Type</button>
            </div>
        </form>
    </div>
</div>
@endsection

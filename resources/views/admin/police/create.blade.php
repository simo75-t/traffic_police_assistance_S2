@extends('admin.layout')

@section('title', 'Create Police Account')

@section('content')
<div class="container mt-5">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="bi bi-person-plus"></i> Create New Police Account</h4>
        </div>

        <div class="card-body bg-light">
            {{-- ✅ عرض الأخطاء إن وجدت --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>There were some problems with your input:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ✅ نموذج إنشاء الحساب --}}
            <form action="{{ route('admin.users.create') }}" method="POST" class="p-2">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Officer Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter officer name" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="example@police.gov" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-check-circle"></i> Create Account
                    </button>
                    <a href="{{ route('admin.users.store') }}" class="btn btn-secondary px-4 ms-2">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ✅ تنسيقات إضافية خفيفة --}}
<style>
    .card {
        border-radius: 12px;
    }
    .form-label {
        color: #333;
    }
    .btn {
        border-radius: 6px;
    }
</style>
@endsection

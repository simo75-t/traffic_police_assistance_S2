@extends('admin.layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="container mt-5" style="max-width: 600px;">
    <h3 class="mb-4 text-center">Edit User</h3>

    {{-- عرض الأخطاء --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.users.saveupdate', $user->id) }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
        </div>

        <div class="form-check mb-3">
            <input type="hidden" name="is_active" value="disactive"> {{-- قيمة افتراضية إذا لم يتم التحديد --}}
            <input type="checkbox" class="form-check-input" name="is_active" value="active" id="is_active"
                   {{ old('is_active', $user->is_active ? 'active' : '') == 'active' ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Active</label>
        </div>

        <button type="submit" class="btn btn-primary w-100">Save Changes</button>
    </form>
</div>
@endsection

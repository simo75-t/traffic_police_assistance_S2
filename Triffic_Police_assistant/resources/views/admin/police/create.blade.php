@extends('admin.layouts.app')

@section('title', 'Create User')

@section('content')
<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="page-title">Create Police Account</h1>
        <p class="page-subtitle">Create a police officer or police manager account with clear access control.</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-light border rounded-4 px-4 py-3">Back to Accounts</a>
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

        <form action="{{ route('admin.users.store') }}" method="POST" class="row g-4">
            @csrf

            <div class="col-md-6">
                <label class="form-label fw-bold">Name</label>
                <input type="text" name="name" class="form-control rounded-4" value="{{ old('name') }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Email</label>
                <input type="email" name="email" class="form-control rounded-4" value="{{ old('email') }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Password</label>
                <input type="password" name="password" class="form-control rounded-4" required>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold">Role</label>
                <select name="role" class="form-select rounded-4" required>
                    <option value="Police_officer" @selected(old('role') === 'Police_officer')>Police Officer</option>
                    <option value="Police_manager" @selected(old('role') === 'Police_manager')>Police Manager</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold">Status</label>
                <select name="is_active" class="form-select rounded-4" required>
                    <option value="1" @selected(old('is_active', '1') == '1')>Active</option>
                    <option value="0" @selected(old('is_active') == '0')>Inactive</option>
                </select>
            </div>

            <div class="col-12 d-flex gap-2 justify-content-end">
                <a href="{{ route('admin.users.index') }}" class="btn btn-light border rounded-4 px-4">Cancel</a>
                <button type="submit" class="btn text-white rounded-4 px-4" style="background: linear-gradient(135deg, #1a5d87, #10243d);">Create User</button>
            </div>
        </form>
    </div>
</div>
@endsection

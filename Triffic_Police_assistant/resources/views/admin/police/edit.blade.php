@extends('admin.layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="page-title">Edit User</h1>
        <p class="page-subtitle">Update the selected account without breaking role-based access.</p>
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

        <form action="{{ route('admin.users.saveupdate', $user->id) }}" method="POST" class="row g-4">
            @csrf
            @method('PATCH')

            <div class="col-md-6">
                <label class="form-label fw-bold">Name</label>
                <input type="text" name="name" class="form-control rounded-4" value="{{ old('name', $user->name) }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Email</label>
                <input type="email" name="email" class="form-control rounded-4" value="{{ old('email', $user->email) }}" required>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">Status</label>
                <select name="is_active" class="form-select rounded-4" required>
                    <option value="1" @selected((string) old('is_active', (int) $user->is_active) === '1')>Active</option>
                    <option value="0" @selected((string) old('is_active', (int) $user->is_active) === '0')>Inactive</option>
                </select>
            </div>

            <div class="col-12 d-flex gap-2 justify-content-end">
                <a href="{{ route('admin.users.index') }}" class="btn btn-light border rounded-4 px-4">Cancel</a>
                <button type="submit" class="btn text-white rounded-4 px-4" style="background: linear-gradient(135deg, #1a5d87, #10243d);">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('admin.layouts.app')

@section('title', 'User Details')

@section('content')
<div class="mb-4">
    <h1 class="page-title">User Details</h1>
    <p class="page-subtitle">Review the selected police account details.</p>
</div>

<div class="content-card">
    <div class="content-card-body">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="border rounded-4 p-4 h-100">
                    <small class="text-muted d-block mb-2">Name</small>
                    <strong>{{ $user->name }}</strong>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded-4 p-4 h-100">
                    <small class="text-muted d-block mb-2">Email</small>
                    <strong>{{ $user->email }}</strong>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded-4 p-4 h-100">
                    <small class="text-muted d-block mb-2">Role</small>
                    <strong>{{ str_replace('_', ' ', $user->role) }}</strong>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded-4 p-4 h-100">
                    <small class="text-muted d-block mb-2">Status</small>
                    <strong>{{ $user->is_active ? 'Active' : 'Inactive' }}</strong>
                </div>
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning rounded-4 px-4">Edit</a>
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary rounded-4 px-4">Back</a>
        </div>
    </div>
</div>
@endsection

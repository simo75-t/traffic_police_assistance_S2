@extends('admin.layouts.app')

@section('title', 'Police Accounts')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="page-title">Police Accounts</h1>
        <p class="page-subtitle">Manage police managers and police officers from one admin workspace.</p>
    </div>

    <a href="{{ route('admin.users.create') }}" class="btn text-white rounded-4 px-4 py-3 fw-bold" style="background: linear-gradient(135deg, #1a5d87, #10243d);">
        <i class="bi bi-plus-circle me-2"></i>Create New Account
    </a>
</div>

<div class="content-card mb-4">
    <div class="content-card-body">
        <form method="GET" action="{{ route('admin.users.index') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-bold">Search by Name</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control rounded-4" placeholder="Officer or manager name">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Status</label>
                <select name="status" class="form-select rounded-4">
                    <option value="">All statuses</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Role</label>
                <select name="role" class="form-select rounded-4">
                    <option value="">All roles</option>
                    <option value="Police_officer" @selected(($filters['role'] ?? '') === 'Police_officer')>Police Officer</option>
                    <option value="Police_manager" @selected(($filters['role'] ?? '') === 'Police_manager')>Police Manager</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">Sort</label>
                <select name="order_direction" class="form-select rounded-4">
                    <option value="desc" @selected(($filters['order_direction'] ?? 'desc') === 'desc')>Newest</option>
                    <option value="asc" @selected(($filters['order_direction'] ?? '') === 'asc')>Oldest</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-4 px-4">Apply Filters</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-light rounded-4 px-4 border">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="content-card">
    <div class="content-card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $p)
                        <tr>
                            <td>{{ $p->id }}</td>
                            <td class="fw-bold">{{ $p->name }}</td>
                            <td>{{ $p->email }}</td>
                            <td>{{ str_replace('_', ' ', $p->role) }}</td>
                            <td>
                                <span class="badge rounded-pill bg-{{ $p->is_active ? 'success' : 'danger' }}">
                                    {{ $p->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end flex-wrap gap-2">
                                    <a href="{{ route('admin.users.show', $p->id) }}" class="btn btn-light border rounded-4">View</a>
                                    <a href="{{ route('admin.users.edit', $p->id) }}" class="btn btn-warning rounded-4">Edit</a>

                                    <form action="{{ route('admin.users.toggle', $p->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline-{{ $p->is_active ? 'danger' : 'success' }} rounded-4">
                                            {{ $p->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.users.delete', $p->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this account?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger rounded-4">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No police accounts found for the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $users->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection

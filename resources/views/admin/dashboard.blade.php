@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="mb-4">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Monitor account activity and jump quickly into the admin workflows.</p>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="content-card">
            <div class="content-card-body text-white" style="background: linear-gradient(135deg, #10243d, #1a5d87);">
                <small class="d-block opacity-75 mb-2">Total Users</small>
                <h2 class="mb-0 fw-bold">{{ $stats['totalUsers'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="content-card">
            <div class="content-card-body text-white" style="background: linear-gradient(135deg, #0d5f46, #169c6b);">
                <small class="d-block opacity-75 mb-2">Active Users</small>
                <h2 class="mb-0 fw-bold">{{ $stats['activeUsers'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="content-card">
            <div class="content-card-body" style="background: linear-gradient(135deg, #efd28a, #d7a93c); color: #132238;">
                <small class="d-block opacity-75 mb-2">Inactive Users</small>
                <h2 class="mb-0 fw-bold">{{ $stats['inactiveUsers'] }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="content-card">
            <div class="content-card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h5 mb-0 fw-bold">Latest Users</h3>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-light border rounded-4">Manage Users</a>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stats['latestUsers'] as $user)
                                <tr>
                                    <td>{{ $user->id }}</td>
                                    <td class="fw-bold">{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ str_replace('_', ' ', $user->role) }}</td>
                                    <td>
                                        <span class="badge rounded-pill bg-{{ $user->is_active ? 'success' : 'danger' }}">
                                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No users found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="content-card mb-4">
            <div class="content-card-body">
                <h3 class="h5 fw-bold mb-2">Quick Actions</h3>
                <p class="text-muted">Open the most used admin tasks directly from the dashboard.</p>
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.users.create') }}" class="btn text-white rounded-4" style="background: linear-gradient(135deg, #1a5d87, #10243d);">Create Police Account</a>
                    <a href="{{ route('admin.violationTypes.create') }}" class="btn rounded-4" style="background: linear-gradient(135deg, #efd28a, #d7a93c); color: #132238;">Create Violation Type</a>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="content-card-body">
                <h3 class="h5 fw-bold mb-2">Status Summary</h3>
                <p class="text-muted mb-4">A quick split between active and inactive accounts.</p>
                <div class="d-flex justify-content-between align-items-center border rounded-4 p-3 mb-2">
                    <span>Active</span>
                    <strong>{{ $stats['activeUsers'] }}</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center border rounded-4 p-3">
                    <span>Inactive</span>
                    <strong>{{ $stats['inactiveUsers'] }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

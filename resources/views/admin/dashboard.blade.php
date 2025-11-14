@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">

    {{-- 🔹 البطاقات --}}
    <div class="row text-center mb-4">
        <div class="col-md-4 mb-3">
            <div class="card bg-primary text-white border-0 shadow-sm">
                <div class="card-body py-3">
                    <h6>Total Users</h6>
                    <h3 class="fw-bold">{{ $stats['totalUsers'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card bg-success text-white border-0 shadow-sm">
                <div class="card-body py-3">
                    <h6>Active Users</h6>
                    <h3 class="fw-bold">{{ $stats['activeUsers'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card bg-danger text-white border-0 shadow-sm">
                <div class="card-body py-3">
                    <h6>Inactive Users</h6>
                    <h3 class="fw-bold">{{ $stats['inactiveUsers'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- 🔹 الرسم البياني --}}
    <div class="text-center mb-4">
        <div class="mx-auto" style="width: 280px; height: 280px;">
            <canvas id="usersChart"></canvas>
        </div>
    </div>

    {{-- 🔹 جدول آخر 5 مستخدمين --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light fw-bold">
            Last 5 Added Users
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 text-center align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stats['latestUsers'] as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td class="text-capitalize">{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->phone ?? '-' }}</td>
                                <td>{{ $user->role }}</td>
                                <td>
                                    @if($user->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-muted">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('usersChart');
    if(ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Inactive'],
                datasets: [{
                    data: [{{ $stats['activeUsers'] }}, {{ $stats['inactiveUsers'] }}],
                    backgroundColor: ['#28a745', '#dc3545'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                cutout: '70%',
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
});
</script>
@endsection
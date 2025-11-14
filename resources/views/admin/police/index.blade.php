@extends('admin.layout')

@section('title', 'Manage Police Accounts')

@section('content')
<style>
    /* 🔹 تنسيقات عامة للصفحة */
    body {
        padding-top: 40px;
        /* حتى ما يختفي المحتوى وراء أي شريط */
    }

    .app-content-header {
        margin-bottom: 15px;
    }

    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
    }

    .btn-primary:hover {
        background-color: #0056b3;
    }

    .card {
        border-radius: 10px;
        border: none;
    }

    .card-header {
        background: #f8f9fa;
        font-weight: 600;
    }

    .table th,
    .table td {
        vertical-align: middle;
    }
</style>

<div class="app-content-header">
    <div class="container-fluid">
        <div class="row align-items-center mb-3">
            <div class="col-md-6 col-12 mb-2 mb-md-0">
                <h3 class="m-0">Police Accounts</h3>
            </div>
            <div class="col-md-6 col-12 text-md-end text-center">
                <a href="{{ route("admin.users.create") }}" class="btn btn-primary px-4 py-2 shadow-sm">
                    <i class="bi bi-plus-circle"></i> Create New Account
                </a>

            </div>
        </div>
    </div>
</div>

<div class="app-content">
    <div class="container-fluid">
        <div class="card shadow-sm">
            <div class="card-body">

                {{-- Table --}}
                <div class="table-responsive">
                    <table class="table align-middle table-hover text-center mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Officer Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $p)
                            <tr>
                                <td>{{ $p['id'] }}</td>
                                <td class="text-capitalize">{{ $p['name'] }}</td>
                                <td>{{ $p['email'] }}</td>
                                <td>
                                    @if($p['status'] == 'active')
                                    <span class="badge bg-success">Active</span>
                                    @else
                                    <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        {{-- Edit --}}
                                        <a href="{{ route("admin.users.edit", $p['id']) }}" class="btn btn-warning btn-sm px-3">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>

                                        {{-- Toggle Status --}}
                                        <form action="{{ route("admin.users.toggle", $p['id']) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-outline-{{ $p['status'] == 'active' ? 'danger' : 'success' }} btn-sm px-3">
                                                <i class="bi bi-{{ $p['status'] == 'active' ? 'slash-circle' : 'check-circle' }}"></i>
                                                {{ $p['status'] == 'active' ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>

                                        {{-- Delete --}}
                                        <form action="{{ route("admin.users.delete", $p['id']) }}" method="POST" style="display:inline;"
                                            onsubmit="return confirm('⚠️ Are you sure you want to delete this police account?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm px-3">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>

                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-muted">No police accounts found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection


@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

@endsection
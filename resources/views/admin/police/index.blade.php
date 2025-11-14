@extends('admin.layout')

@section('title', 'Manage Police Accounts')

@section('content')

<style>
    .app-content-header {
        margin-bottom: 20px;
    }

    .card {
        border-radius: 12px;
        border: none;
    }

    .table th,
    .table td {
        vertical-align: middle;
    }
</style>

<div class="app-content-header">
    <div class="container-fluid">
        <div class="row align-items-center justify-content-between">
            <div class="col-md-6">
                <h3 class="m-0">Police Accounts</h3>
            </div>
            <div class="col-md-6 text-md-end text-center">
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary px-4 py-2 shadow-sm">
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
                    <table class="table table-bordered table-hover text-center mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th width="25%">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($users as $p)
                            <tr>
                                <td>{{ $p->id }}</td>

                                <td class="text-capitalize">{{ $p->name }}</td>

                                <td>{{ $p->email }}</td>

                                {{-- Phone --}}
                                <td>
                                    {{ $p->Phone ? $p->Phone : '—' }}
                                </td>

                                {{-- Role --}}
                                <td class="text-capitalize">
                                    {{ str_replace('_', ' ', $p->role) }}
                                </td>

                                {{-- Status --}}
                                <td>
                                    @if($p->is_active)
                                    <span class="badge bg-success">Active</span>
                                    @else
                                    <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td>
                                    <div class="d-flex justify-content-center gap-2">

                                        {{-- Edit --}}
                                        <a href="{{ route('admin.users.edit', $p->id) }}"
                                            class="btn btn-warning btn-sm px-3">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>

                                        {{-- Toggle Status --}}
                                        <form action="{{ route('admin.users.toggle', $p->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="btn btn-outline-{{ $p->is_active ? 'danger' : 'success' }} btn-sm px-3">
                                                <i class="bi bi-{{ $p->is_active ? 'slash-circle' : 'check-circle' }}"></i>
                                                {{ $p->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>

                                        {{-- Delete Button with confirmation --}}
                                        <form action="{{ route('admin.users.delete', $p->id) }}" method="POST"
                                            onsubmit="return confirm('are you sure you want to delete this account ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm px-3">
                                                <i class="bi bi-trash"></i> delete
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>

                            @empty
                            <tr>
                                <td colspan="7" class="text-muted">No police accounts found.</td>
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
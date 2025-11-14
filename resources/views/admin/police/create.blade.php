@extends('admin.layout')

@section('content')
<div class="container py-4">

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Create New Police officer </h4>
        </div>

        <div class="card-body">

            {{-- Validation Errors --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf

                <div class="row mb-3">

                    {{-- Name --}}
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input 
                            type="text" 
                            name="name" 
                            class="form-control form-control-lg"
                            value="{{ old('name') }}" 
                            required>
                    </div>

                    {{-- Email --}}
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input 
                            type="email" 
                            name="email" 
                            class="form-control form-control-lg"
                            value="{{ old('email') }}" 
                            required>
                    </div>
                </div>

                <div class="row mb-3">

                    {{-- Phone (Optional) --}}
                    <div class="col-md-6">
                        <label class="form-label">Phone (Optional)</label>
                        <input 
                            type="text" 
                            name="phone" 
                            class="form-control form-control-lg"
                            value="{{ old('phone') }}">
                    </div>

                    {{-- Password --}}
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input 
                            type="password" 
                            name="password" 
                            class="form-control form-control-lg"
                            required>
                    </div>

                </div>

                <div class="row mb-3">

                    {{-- Role (Admin removed) --}}
                    <div class="col-md-6">
                        <label class="form-label">User Role</label>
                        <select name="role" class="form-select form-select-lg" required>
                            <option value="Police_officer" {{ old('role') == 'Police_officer' ? 'selected' : '' }}>
                                Police Officer
                            </option>

                            <option value="Police_manager" {{ old('role') == 'Police_manager' ? 'selected' : '' }}>
                                Police Manager
                            </option>
                        </select>
                    </div>

                    {{-- Status --}}
                    <div class="col-md-6">
                        <label class="form-label">Account Status</label>
                        <select name="is_active" class="form-select form-select-lg" required>
                            <option value="1" {{ old('is_active') == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary btn-lg">
                        Create User
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>
@endsection
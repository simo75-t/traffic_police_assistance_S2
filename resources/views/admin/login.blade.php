@extends('admin.layouts.auth')

@section('title', 'Admin Login')

@section('auth_header')
    <h2 class="mb-2 fw-bold">Admin Control Panel</h2>
    <p class="mb-0 opacity-75">Sign in with an active administrator account to manage officers and violation types.</p>
@endsection

@section('content')
    @if ($errors->has('login'))
        <div class="alert alert-danger rounded-4">{{ $errors->first('login') }}</div>
    @endif

    <form action="{{ route('admin.login.submit') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label fw-bold">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg rounded-4" required>
        </div>
        <div class="mb-4">
            <label class="form-label fw-bold">Password</label>
            <input type="password" name="password" class="form-control form-control-lg rounded-4" required>
        </div>
        <button type="submit" class="btn w-100 text-white fw-bold rounded-4 py-3" style="background: linear-gradient(135deg, #d7a93c, #a97817);">
            Login
        </button>
    </form>
@endsection

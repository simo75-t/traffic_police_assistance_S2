@extends('policemanager.layouts.auth')

@section('title', 'Police Manager Login')
@section('heading', 'Police Manager Login')
@section('subheading', 'Sign in with an active police manager account to access violations and appeal workflows.')

@section('content')
    @if ($errors->has('login'))
        <div class="alert alert-danger">
            {{ $errors->first('login') }}
        </div>
    @endif

    @error('email')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    @error('password')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <form action="{{ route('policemanager.login.submit') }}" method="POST">
        @csrf

        <label for="email">Email</label>
        <input
            id="email"
            type="email"
            name="email"
            value="{{ old('email') }}"
            placeholder="manager@traffic.local"
            required
            autofocus
        >

        <label for="password">Password</label>
        <input
            id="password"
            type="password"
            name="password"
            placeholder="Enter your password"
            required
        >

        <button class="btn-primary" type="submit">Login</button>
    </form>

    <p class="helper-text">
        Access is limited to users whose role is <strong>Police_manager</strong> and whose account is active.
    </p>
@endsection

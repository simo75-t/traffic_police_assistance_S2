@extends('policemanager.layouts.app')

@section('title', 'Police Manager Dashboard')
@section('page_title', 'Dashboard')
@section('page_description', 'Entry point for the police manager review workflow after authentication.')

@section('content')
    <section class="stack">
        <div class="stats-grid">
            <article class="stat-card">
                <small>Total violations</small>
                <strong>{{ $stats['violationsCount'] }}</strong>
            </article>

            <article class="stat-card">
                <small>Total appeals</small>
                <strong>{{ $stats['appealsCount'] }}</strong>
            </article>

            <article class="stat-card">
                <small>Pending appeals</small>
                <strong>{{ $stats['pendingAppealsCount'] }}</strong>
            </article>
        </div>

        <div class="actions-grid">
            <article class="action-card">
                <h3>View Violations</h3>
                <p>Open the violations screen to inspect reported violations and filter them by reporter, type, vehicle, or occurrence time.</p>
                <a class="btn btn-primary" href="{{ route('policemanager.violations.index') }}">Open Violations</a>
            </article>

            <article class="action-card">
                <h3>View Appeals</h3>
                <p>Review submitted appeals, inspect case details, and update the decision status from the list or the detailed page.</p>
                <a class="btn btn-primary" href="{{ route('policemanager.appeals.index') }}">Open Appeals</a>
            </article>

            <article class="action-card">
                <h3>Logout</h3>
                <p>End the authenticated session securely by invalidating the current session and regenerating the CSRF token.</p>
                <form action="{{ route('policemanager.logout') }}" method="POST">
                    @csrf
                    <button class="btn btn-warning" type="submit">Logout</button>
                </form>
            </article>
        </div>
    </section>
@endsection

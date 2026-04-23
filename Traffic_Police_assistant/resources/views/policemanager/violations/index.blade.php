@extends('policemanager.layouts.app')

@section('title', 'Violations')
@section('page_title', 'Violations')
@section('page_description', 'Review all registered violations and apply a direct search filter from the query string UI.')

@section('content')
    <section class="surface">
        <div class="surface-body stack">
            <form class="filter-bar" action="{{ route('policemanager.violations.index') }}" method="GET">
                <select name="search_type">
                    <option value="">Select filter</option>
                    <option value="vehicle_id" @selected($searchType === 'vehicle_id')>Plate Number</option>
                    <option value="violation_type" @selected($searchType === 'violation_type')>Violation Type</option>
                    <option value="reporter" @selected($searchType === 'reporter')>Reporter</option>
                    <option value="occurred_at" @selected($searchType === 'occurred_at')>Occurred At</option>
                </select>

                <input
                    type="text"
                    name="search"
                    value="{{ $searchValue }}"
                    placeholder="Enter search value"
                >

                <button class="btn btn-primary" type="submit">Search</button>
                <a class="btn btn-secondary" href="{{ route('policemanager.violations.index') }}">Reset</a>
            </form>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Plate Number</th>
                            <th>Violation Type</th>
                            <th>Street</th>
                            <th>Fine Amount</th>
                            <th>Reporter</th>
                            <th>Occurred At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($violations as $violation)
                            <tr>
                                <td>{{ $violation['vehicle_plate_number'] }}</td>
                                <td>{{ $violation['violation_type_name'] }}</td>
                                <td>{{ $violation['street_name'] }}</td>
                                <td>{{ number_format((float) $violation['fine_amount'], 2) }}</td>
                                <td>{{ $violation['reporter_name'] }}</td>
                                <td>{{ $violation['occurred_at'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty-state">No violations were found for the current filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection

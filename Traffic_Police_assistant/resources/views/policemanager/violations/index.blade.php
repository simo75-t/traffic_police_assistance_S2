@extends('policemanager.layouts.app')

@section('title', 'المخالفات')
@section('page_title', 'المخالفات')
@section('page_description', 'عرض جميع المخالفات المسجلة مع إمكانية البحث المباشر باستخدام الفلاتر.')

@section('content')
    <section class="surface">
        <div class="surface-body stack">
            <form class="filter-bar" action="{{ route('policemanager.violations.index') }}" method="GET">
                <select name="search_type">
                    <option value="">اختر نوع البحث</option>
                    <option value="vehicle_id" @selected($searchType === 'vehicle_id')>رقم اللوحة</option>
                    <option value="violation_type" @selected($searchType === 'violation_type')>نوع المخالفة</option>
                    <option value="reporter" @selected($searchType === 'reporter')>المبلّغ</option>
                    <option value="occurred_at" @selected($searchType === 'occurred_at')>تاريخ المخالفة</option>
                </select>

                <input
                    type="text"
                    name="search"
                    value="{{ $searchValue }}"
                    placeholder="أدخل قيمة البحث"
                >

                <button class="btn btn-primary" type="submit">بحث</button>
                <a class="btn btn-secondary" href="{{ route('policemanager.violations.index') }}">إعادة ضبط</a>
            </form>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>رقم اللوحة</th>
                            <th>نوع المخالفة</th>
                            <th>الشارع</th>
                            <th>قيمة الغرامة</th>
                            <th>المبلّغ</th>
                            <th>تاريخ المخالفة</th>
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
                                <td colspan="6" class="empty-state">لا توجد مخالفات مطابقة للبحث الحالي.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $violations->links() }}
        </div>
    </section>
@endsection

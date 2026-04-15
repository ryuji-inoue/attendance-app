@extends('layouts.app')

@section('title', 'スタッフ別勤怠一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/staff-attendance.css') }}">
@endsection

@section('content')
    <h1 class="section-title">{{ $user->name }}さんの勤怠</h1>

    <div class="attendance-list__header">
        <div class="attendance-list__nav">
            <a href="/admin/attendance/staff/{{ $user->id }}?month={{ $prevMonth }}" class="attendance-list__month-link">
                <span class="attendance-list__nav-arrow">&larr;</span> 前月
            </a>
            <span class="attendance-list__current-month">
                <span class="attendance-list__calendar-icon">📅</span> {{ $currentMonthDisplay }}
            </span>
            <a href="/admin/attendance/staff/{{ $user->id }}?month={{ $nextMonth }}" class="attendance-list__month-link">
                翌月 <span class="attendance-list__nav-arrow">&rarr;</span>
            </a>
        </div>
    </div>

    <div class="attendance-list__table-container">
        <table class="attendance-list__table">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($attendances as $attendance)
                    <tr>
                        <td>{{ $attendance->formatted_date }}</td>
                        <td>{{ $attendance->formatted_clock_in }}</td>
                        <td>{{ $attendance->formatted_clock_out }}</td>
                        <td>{{ $attendance->total_break }}</td>
                        <td>{{ $attendance->total_working }}</td>
                        <td><a href="/attendance/detail/{{ $attendance->id }}" class="attendance-list__detail-link">詳細</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="attendance-list__footer">
        <a href="/admin/attendance/staff/{{ $user->id }}/export?month={{ $exportMonth }}"
            class="attendance-list__export-link">CSV出力</a>
    </div>
@endsection
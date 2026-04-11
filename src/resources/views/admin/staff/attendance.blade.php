@extends('layouts.app')

@section('title', $user->name . 'の勤怠')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendance.css') }}">
@endsection

@section('content')
<h1 class="section-title">{{ $user->name }}さんの勤怠</h1>

<div class="attendance-list__header">
    <div class="attendance-list__nav">
        <a href="/admin/attendance/staff/{{ $user->id }}?month={{ $prevMonth }}" class="attendance-list__month-link">前月</a>
        <span class="attendance-list__current-month">
            <span class="attendance-list__calendar-icon">📅</span> {{ $currentMonthDisplay }}
        </span>
        <a href="/admin/attendance/staff/{{ $user->id }}?month={{ $nextMonth }}" class="attendance-list__month-link">翌月</a>
    </div>
    <div class="attendance-list__export">
        <a href="/admin/attendance/staff/{{ $user->id }}/export?month={{ request('month', \Carbon\Carbon::now()->format('Y-m')) }}" class="attendance-list__export-link">CSV出力</a>
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
                    <td>{{ \Carbon\Carbon::parse($attendance->date)->isoFormat('MM/DD(ddd)') }}</td>
                    <td>{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}</td>
                    <td>{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}</td>
                    <td>{{ $attendance->total_break }}</td>
                    <td>{{ $attendance->total_working }}</td>
                    <td><a href="/attendance/detail/{{ $attendance->id }}" class="attendance-list__detail-link">詳細</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection

@extends('layouts.app')

@section('title', '勤怠一覧(管理者)')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendance.css') }}">
@endsection

@section('content')
<h1 class="section-title">{{ $currentDateDisplay }} の勤怠</h1>

<div class="attendance-list__header">
    <div class="attendance-list__nav">
        <a href="/admin/attendance/list?date={{ $prevDate }}" class="attendance-list__month-link">前日</a>
        <span class="attendance-list__current-month">
            <span class="attendance-list__calendar-icon">📅</span> {{ $currentDateDisplay }}
        </span>
        <a href="/admin/attendance/list?date={{ $nextDate }}" class="attendance-list__month-link">翌日</a>
    </div>
</div>

<div class="attendance-list__table-container">
    <table class="attendance-list__table">
        <thead>
            <tr>
                <th>名前</th>
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
                    <td>{{ $attendance->user->name }}</td>
                    <td>{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}</td>
                    <td>{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}</td>
                    <td>{{ $attendance->total_break }}</td>
                    <td>{{ $attendance->total_working }}</td>
                    <td><a href="/attendance/detail/{{ $attendance->id }}" class="attendance-list__detail-link">詳細</a></td>
                </tr>
            @endforeach
            @if ($attendances->isEmpty())
                <tr>
                    <td colspan="6" class="attendance-list__empty">この日の勤怠データはありません</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
@endsection

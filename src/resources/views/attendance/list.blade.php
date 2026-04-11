@extends('layouts.app')

@section('title', '勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/list.css') }}">
@endsection

@section('content')
<h1 class="section-title">勤怠一覧</h1>

@if (session('success'))
    <div class="attendance-list__alert">
        {{ session('success') }}
    </div>
@endif

<div class="attendance-list__header">
    <div class="attendance-list__nav">
        <a href="/attendance/list?month={{ $prevMonth }}" class="attendance-list__month-link">前月</a>
        <span class="attendance-list__current-month">
            <span class="attendance-list__calendar-icon">📅</span> {{ $currentMonthDisplay }}
        </span>
        <a href="/attendance/list?month={{ $nextMonth }}" class="attendance-list__month-link">翌月</a>
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

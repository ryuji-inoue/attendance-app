@extends('layouts.app')

@section('title', '修正申請承認')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/correction.css') }}">
@endsection

@section('content')
    <h1 class="section-title">勤怠詳細</h1>

    <div class="attendance-detail__container">
        <form action="/stamp_correction_request/approve/{{ $correction->id }}" method="POST">
            @csrf
            <table class="attendance-detail__table">
                <tr>
                    <th>名前</th>
                    <td class="attendance-detail__value">{{ $correction->user->name }}</td>
                </tr>
                <tr>
                    <th>日付</th>
                    <td class="attendance-detail__value">
                        {{ \Carbon\Carbon::parse($correction->attendance->date)->isoFormat('YYYY年') }}
                        <span class="attendance-detail__date-item">{{ \Carbon\Carbon::parse($correction->attendance->date)->isoFormat('M月D日') }}</span>
                    </td>
                </tr>
                <tr>
                    <th>出勤・退勤</th>
                    <td class="attendance-detail__value">
                        {{ \Carbon\Carbon::parse($correction->clock_in)->format('H:i') }}
                        <span class="attendance-detail__tilde">〜</span>
                        {{ \Carbon\Carbon::parse($correction->clock_out)->format('H:i') }}
                    </td>
                </tr>
                @foreach ($correction->correctionBreaks as $index => $break)
                    <tr>
                        <th>休憩{{ count($correction->correctionBreaks) > 1 ? $index + 1 : '' }}</th>
                        <td class="attendance-detail__value">
                            {{ \Carbon\Carbon::parse($break->break_start)->format('H:i') }}
                            <span class="attendance-detail__tilde">〜</span>
                            {{ \Carbon\Carbon::parse($break->break_end)->format('H:i') }}
                        </td>
                    </tr>
                @endforeach
                <tr class="attendance-detail__row--no-border">
                    <th>備考</th>
                    <td class="attendance-detail__value">
                        {{ $correction->note }}
                    </td>
                </tr>
            </table>

            @if ($correction->status === 'pending')
                <div class="attendance-detail__footer">
                    <button type="submit" class="attendance-detail__submit">承認</button>
                </div>
            @else
                <p class="attendance-detail__approved-msg">* この申請は承認済みです</p>
            @endif
        </form>
    </div>
@endsection
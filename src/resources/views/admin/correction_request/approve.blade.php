@extends('layouts.app')

@section('title', '修正申請承認')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/correction-request-approve.css') }}">
@endsection

@section('content')
    <h1 class="section-title">勤怠詳細</h1>

    <div class="attendance-detail__container">
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
            @php
                $cBreaks = $correction->correctionBreaks;
                // 入力済み数 + 1 か、最低2行を表示
                $displayCount = max(2, count($cBreaks) + 1);
            @endphp
            @for ($i = 0; $i < $displayCount; $i++)
                <tr>
                    <th>休憩{{ $i > 0 ? $i + 1 : '' }}</th>
                    <td class="attendance-detail__value">
                        @if(isset($cBreaks[$i]))
                            {{ \Carbon\Carbon::parse($cBreaks[$i]->break_start)->format('H:i') }}
                            <span class="attendance-detail__tilde">〜</span>
                            {{ \Carbon\Carbon::parse($cBreaks[$i]->break_end)->format('H:i') }}
                        @endif
                    </td>
                </tr>
            @endfor
            <tr class="attendance-detail__row--no-border">
                <th>備考</th>
                <td class="attendance-detail__value">
                    {{ $correction->note }}
                </td>
            </tr>
        </table>
    </div>

    <form action="/stamp_correction_request/approve/{{ $correction->id }}" method="POST">
        @csrf
        <div class="attendance-detail__footer">
            @if ($correction->status === 'pending')
                <button type="submit" class="attendance-detail__submit">承認</button>
            @else
                <button type="button" class="attendance-detail__submit--approved" disabled>承認済み</button>
            @endif
        </div>
    </form>
@endsection
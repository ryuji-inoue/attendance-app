@extends('layouts.app')

@section('title', '勤怠詳細')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance/detail.css') }}">
@endsection

@section('content')
    <h1 class="section-title">勤怠詳細</h1>

    <form action="/attendance/detail/{{ $attendance->id }}" method="POST">
        @csrf
        <div class="attendance-detail__container">
            <table class="attendance-detail__table">
                <tr>
                    <th>名前</th>
                    <td><span class="attendance-detail__user-name">{{ $attendance->user->name }}</span></td>
                </tr>
                <tr>
                    <th>日付</th>
                    <td>
                        <span class="attendance-detail__date-text">
                            {{ \Carbon\Carbon::parse($attendance->date)->format('Y年 n月j日') }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>出勤・退勤</th>
                    <td>
                        @if($canEdit)
                            <input type="time" name="clock_in" class="attendance-detail__input"
                                value="{{ $displayData['clock_in'] }}">
                            <span class="attendance-detail__tilde">〜</span>
                            <input type="time" name="clock_out" class="attendance-detail__input"
                                value="{{ $displayData['clock_out'] }}">

                            {{-- エラー表示 --}}
                            @error('clock_in')<p class="error-message">{{ $message }}</p>@enderror
                            @error('clock_out')<p class="error-message">{{ $message }}</p>@enderror
                        @else
                            <span class="attendance-detail__text">{{ $displayData['clock_in'] }}</span>
                            <span class="attendance-detail__tilde">〜</span>
                            <span class="attendance-detail__text">{{ $displayData['clock_out'] }}</span>
                        @endif
                    </td>
                </tr>

                {{-- 休憩時間のループ --}}
                @foreach ($displayData['breaks'] as $i => $break)
                    <tr>
                        <th>休憩{{ $i + 1 }}</th>
                        <td>
                            @if($canEdit)
                                <input type="time" name="breaks[{{ $i }}][start]" class="attendance-detail__input"
                                    value="{{ $break['start'] }}">
                                <span class="attendance-detail__tilde">〜</span>
                                <input type="time" name="breaks[{{ $i }}][end]" class="attendance-detail__input"
                                    value="{{ $break['end'] }}">

                                {{-- エラー表示 --}}
                                @error('breaks.' . $i . '.start')<p class="error-message">{{ $message }}</p>@enderror
                                @error('breaks.' . $i . '.end')<p class="error-message">{{ $message }}</p>@enderror
                            @else
                                <span class="attendance-detail__text">{{ $break['start'] }}</span>
                                <span class="attendance-detail__tilde">〜</span>
                                <span class="attendance-detail__text">{{ $break['end'] }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach

                <tr>
                    <th>備考</th>
                    <td>
                        @if($canEdit)
                            <textarea name="note" class="attendance-detail__note">{{ $displayData['note'] }}</textarea>
                            @error('note')<p class="error-message">{{ $message }}</p>@enderror
                        @else
                            <span class="attendance-detail__text-note">{!! nl2br(e($displayData['note'])) !!}</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="attendance-detail__footer">
            @if(Auth::user()->role === 'admin' && $isPending)
                <button type="submit" form="approve-form" class="attendance-detail__submit">承認</button>
            @elseif($canEdit)
                <button type="submit" class="attendance-detail__submit">修正</button>
            @endif
        </div>
    </form>

    {{-- 管理者用承認フォーム --}}
    @if(Auth::user()->role === 'admin' && $isPending)
        <form id="approve-form" action="/stamp_correction_request/approve/{{ $pendingRequest->id }}" method="POST"
            style="display:none;">
            @csrf
        </form>
    @endif

    @if(!$canEdit && Auth::user()->role !== 'admin')
        <p class="attendance-detail__pending-msg">*承認待ちのため修正はできません。</p>
    @endif
@endsection
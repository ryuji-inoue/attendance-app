@extends('layouts.app')

@section('title', '勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/detail.css') }}">
@endsection

@section('content')
<h1 class="section-title">勤怠詳細</h1>

@if ($isPending)
    <div class="attendance-detail__alert">
        承認待ちのため修正はできません。
    </div>
@endif

<div class="attendance-detail__container">
    <form action="/attendance/detail/{{ $attendance->id }}" method="POST">
        @csrf
        <table class="attendance-detail__table">
            <tr>
                <th>名前</th>
                <td><span class="attendance-detail__user-name">{{ $attendance->user->name }}</span></td>
            </tr>
            <tr>
                <th>日付</th>
                <td>
                    <span class="attendance-detail__date-text">
                        {{ \Carbon\Carbon::parse($attendance->date)->isoFormat('YYYY年') }}
                        <span class="attendance-detail__date-item">{{ \Carbon\Carbon::parse($attendance->date)->isoFormat('M月D日') }}</span>
                    </span>
                </td>
            </tr>
            <tr>
                <th>出勤・退勤 <span class="attendance-detail__required">*必須</span></th>
                <td>
                    <input type="time" name="clock_in" class="attendance-detail__input" value="{{ old('clock_in', $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '') }}" {{ $isPending ? 'readonly' : '' }}>
                    <span class="attendance-detail__tilde">〜</span>
                    <input type="time" name="clock_out" class="attendance-detail__input" value="{{ old('clock_out', $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '') }}" {{ $isPending ? 'readonly' : '' }}>
                    @error('clock_in')
                        <p class="attendance-detail__error">{{ $message }}</p>
                    @enderror
                    @error('clock_out')
                        <p class="attendance-detail__error">{{ $message }}</p>
                    @enderror
                </td>
            </tr>
            @php 
                $breaks = $attendance->breaks;
                $breakCount = count($breaks);
                $displayCount = $breakCount + 1;
            @endphp
            @for ($i = 0; $i < $displayCount; $i++)
                @php 
                    $breakStart = isset($breaks[$i]) ? ($breaks[$i]->break_start ? \Carbon\Carbon::parse($breaks[$i]->break_start)->format('H:i') : '') : '';
                    $breakEnd = isset($breaks[$i]) ? ($breaks[$i]->break_end ? \Carbon\Carbon::parse($breaks[$i]->break_end)->format('H:i') : '') : '';
                @endphp
                <tr>
                    <th>休憩{{ $displayCount > 1 ? $i + 1 : '' }}</th>
                    <td>
                        <input type="time" name="breaks[{{ $i }}][start]" class="attendance-detail__input" value="{{ old("breaks.$i.start", $breakStart) }}" {{ $isPending ? 'readonly' : '' }}>
                        <span class="attendance-detail__tilde">〜</span>
                        <input type="time" name="breaks[{{ $i }}][end]" class="attendance-detail__input" value="{{ old("breaks.$i.end", $breakEnd) }}" {{ $isPending ? 'readonly' : '' }}>
                        @error("breaks.$i.end")
                            <p class="attendance-detail__error">{{ $message }}</p>
                        @enderror
                    </td>
                </tr>
            @endfor
            <tr class="attendance-detail__row--no-border">
                <th>備考 <span class="attendance-detail__required">*必須</span></th>
                <td>
                    <textarea name="note" class="attendance-detail__note" {{ $isPending ? 'readonly' : '' }}>{{ old('note', $attendance->note) }}</textarea>
                    @error('note')
                        <p class="attendance-detail__error">{{ $message }}</p>
                    @enderror
                </td>
            </tr>
        </table>

        <div class="attendance-detail__footer">
            @if (!$isPending)
                <button type="submit" class="attendance-detail__submit">修正</button>
            @endif
        </div>
    </form>
</div>
@endsection

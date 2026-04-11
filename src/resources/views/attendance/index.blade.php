@extends('layouts.app')

@section('title', '打刻')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/index.css') }}">
@endsection

@section('content')
<div class="attendance__content">
    <h1 class="visually-hidden">打刻画面</h1>
    @if (session('success'))
        <div class="attendance__alert">
            {{ session('success') }}
        </div>
    @endif
    <div class="attendance__status">
        {{ $status }}
    </div>

    <div class="attendance__date">
        {{ \Carbon\Carbon::now()->isoFormat('YYYY年M月D日(ddd)') }}
    </div>

    <div class="attendance__time" id="realtime">
        {{ \Carbon\Carbon::now()->format('H:i') }}
    </div>

    <div class="attendance__button-group">
        @if ($status === '勤務外')
            <form action="/attendance/clock-in" method="POST">
                @csrf
                <button type="submit" class="attendance__button attendance__button--black">出勤</button>
            </form>
        @elseif ($status === '出勤中')
            <form action="/attendance/clock-out" method="POST">
                @csrf
                <button type="submit" class="attendance__button attendance__button--black">退勤</button>
            </form>
            <form action="/attendance/break-start" method="POST">
                @csrf
                <button type="submit" class="attendance__button attendance__button--white">休憩入</button>
            </form>
        @elseif ($status === '休憩中')
            <form action="/attendance/break-end" method="POST">
                @csrf
                <button type="submit" class="attendance__button attendance__button--white">休憩戻</button>
            </form>
        @elseif ($status === '退勤済')
            <p class="attendance__complete-msg">お疲れ様でした。</p>
        @endif
    </div>
</div>

<script>
    function updateTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        document.getElementById('realtime').textContent = hours + ':' + minutes;
    }
    updateTime();
    setInterval(updateTime, 1000);
</script>
@endsection

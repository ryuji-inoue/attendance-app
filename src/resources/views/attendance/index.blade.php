@extends('layouts.app')

@section('title', '打刻')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance/index.css') }}">
@endsection

@section('content')
    <div class="attendance__content">
        <div class="attendance__status">
            {{ $status }}
        </div>

        <div class="attendance__date">
            {{ $currentDate }}
        </div>

        <div class="attendance__time" id="realtime">
            {{ $currentTime }}
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
        // DOMの読み込みが完了してから実行
        document.addEventListener('DOMContentLoaded', function() {
            function updateTime() {
                const now = new Date();
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                document.getElementById('realtime').textContent = hours + ':' + minutes;
            }
            // 1秒ごとに更新
            setInterval(updateTime, 1000);
        });
    </script>
@endsection
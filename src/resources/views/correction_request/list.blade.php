@extends('layouts.app')

@section('title', '申請一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/request-list.css') }}">
@endsection

@section('content')
<h1 class="section-title">申請一覧</h1>

<nav class="request-list__nav">
    <ul class="request-list__nav-list">
        <li>
            <a href="/stamp_correction_request/list?status=pending" class="request-list__nav-link {{ $status === 'pending' ? 'request-list__nav-link--active' : '' }}">承認待ち</a>
        </li>
        <li>
            <a href="/stamp_correction_request/list?status=approved" class="request-list__nav-link {{ $status === 'approved' ? 'request-list__nav-link--active' : '' }}">承認済み</a>
        </li>
    </ul>
</nav>

<div class="request-list__table-container">
    <table class="request-list__table">
        <thead>
            <tr>
                <th>状態</th>
                <th>名前</th>
                <th>対象日</th>
                <th>申請理由</th>
                <th>申請日時</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($requests as $request)
                <tr>
                    <td>{{ $request->status === 'pending' ? '承認待ち' : '承認済み' }}</td>
                    <td>{{ Auth::user()->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($request->attendance->date)->format('Y/m/d') }}</td>
                    <td>{{ Str::limit($request->note, 30) }}</td>
                    <td>{{ $request->created_at->format('Y/m/d') }}</td>
                    <td><a href="/attendance/detail/{{ $request->attendance_id }}" class="request-list__detail-link">詳細</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="request-list__pagination">
    {{ $requests->links() }}
</div>
@endsection

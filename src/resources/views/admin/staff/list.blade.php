@extends('layouts.app')

@section('title', 'スタッフ一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/staff-list.css') }}">
@endsection

@section('content')
    <h1 class="section-title">スタッフ一覧</h1>

    <div class="staff-list__table-container">
        <table class="staff-list__table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>メールアドレス</th>
                    <th>月次勤怠</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($staffs as $staff)
                    <tr>
                        <td>{{ $staff->name }}</td>
                        <td>{{ $staff->email }}</td>
                        <td><a href="/admin/attendance/staff/{{ $staff->id }}" class="staff-list__detail-link">詳細</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="staff-list__pagination">
        {{ $staffs->links() }}
    </div>
@endsection
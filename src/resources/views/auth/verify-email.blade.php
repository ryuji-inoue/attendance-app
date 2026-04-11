@extends('layouts.app')

@section('title', 'メール認証の確認')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/verify-email.css') }}">
@endsection

@section('content')
<div class="verify-email__container">
    <h1 class="verify-email__title">メールアドレスの確認</h1>

    @if (session('status') == 'verification-link-sent')
        <div class="verify-email__alert">
            新しい認証リンクを登録したメールアドレスに送信しました。
        </div>
    @endif

    <p class="verify-email__text">
        ご登録ありがとうございます！開始する前に、メールでお送りしたリンクをクリックしてメールアドレスの確認をお願いします。<br>
        もしメールが届いていない場合は、下のボタンから再送信することができます。
    </p>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="verify-email__button">
            認証メール再送
        </button>
    </form>
</div>
@endsection

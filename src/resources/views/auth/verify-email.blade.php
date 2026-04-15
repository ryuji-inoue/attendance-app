@extends('layouts.app')

@section('title', 'メール認証誘導画面')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/verify-email.css') }}">
@endsection

@section('content')
<div class="verify-email">
    <div class="verify-email__container">
        @if (session('status') == 'verification-link-sent')
            <div class="verify-email__alert">
                認証メールを再送しました。
            </div>
        @endif

        <p class="verify-email__text">
            登録していただいたメールアドレスに認証メールを送付しました。<br>
            メール認証を完了してください。
        </p>

        <a href="/" class="verify-email__button-main">
            認証はこちらから
        </a>

        <div class="verify-email__resend">
            <form method="POST" action="{{ route('verification.send') }}" class="verify-email__resend-form">
                @csrf
                <button type="submit" class="verify-email__resend-link">
                    認証メールを再送する
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', '会員登録')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/auth.css') }}">
@endsection

@section('content')
<div class="auth-page">
    <h1 class="auth-page__title">会員登録</h1>

    <form class="auth-form" action="/register" method="POST">
        @csrf
        <div class="auth-form__group">
            <label class="auth-form__label" for="name">名前</label>
            <input class="auth-form__input" type="text" name="name" id="name" value="{{ old('name') }}">
            @error('name')
                <p class="form__error">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-form__group">
            <label class="auth-form__label" for="email">メールアドレス</label>
            <input class="auth-form__input" type="email" name="email" id="email" value="{{ old('email') }}">
            @error('email')
                <p class="form__error">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-form__group">
            <label class="auth-form__label" for="password">パスワード</label>
            <input class="auth-form__input" type="password" name="password" id="password">
            @error('password')
                <p class="form__error">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-form__group">
            <label class="auth-form__label" for="password_confirmation">パスワード確認</label>
            <input class="auth-form__input" type="password" name="password_confirmation" id="password_confirmation">
        </div>

        <button class="auth-form__button" type="submit">登録する</button>
    </form>

    <div class="auth-page__link-group">
        <a class="auth-page__link" href="/login">ログインはこちら</a>
    </div>
</div>
@endsection

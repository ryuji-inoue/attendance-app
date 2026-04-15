@extends('layouts.app')

@section('title', '管理者ログイン')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/auth.css') }}">
@endsection

@section('content')
<div class="auth-page">
    <h1 class="auth-page__title">管理者ログイン</h1>

    <form class="auth-form" action="/admin/login" method="POST">
        @csrf
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

        <button class="auth-form__button" type="submit">管理者ログインする</button>
    </form>
</div>
@endsection

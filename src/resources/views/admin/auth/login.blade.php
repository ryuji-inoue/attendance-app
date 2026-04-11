@extends('layouts.app')

@section('title', '管理者ログイン')

@section('content')
<div class="auth-card">
    <h1 class="auth-card__title">管理者ログイン</h1>

    <form class="form" action="/login" method="POST">
        @csrf
        <div class="form__group">
            <label class="form__label" for="email">メールアドレス</label>
            <input class="form__input" type="email" name="email" id="email" value="{{ old('email') }}">
            @error('email')
                <p class="form__error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form__group">
            <label class="form__label" for="password">パスワード</label>
            <input class="form__input" type="password" name="password" id="password">
            @error('password')
                <p class="form__error">{{ $message }}</p>
            @enderror
        </div>

        <button class="form__button" type="submit">管理者ログイン</button>
    </form>
</div>
@endsection

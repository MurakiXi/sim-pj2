@extends('layouts.app')


@section('title', '会員登録')

@section('css')
<link rel="stylesheet" href="{{ asset('css/register.css') }}">
@endsection

@section('content')

<div class="auth__register-title">
    <h1 class="auth__register-title-text">会員登録</h1>
</div>

<form action="{{ route('register') }}" class="auth__register-form" method="post">
    @csrf
    <div class="auth__register-inner">
        <label class="auth__register-label">ユーザー名</label>
        <div class="auth__register-item">
            <input class="auth__register-input" type="text" name="name" value="{{ old('name') }}">
        </div>
        @error('name')
        <p class="auth__error">{{ $message }}</p>
        @enderror
        <label class="auth__register-label">メールアドレス</label>
        <div class="auth__register-item">
            <input class="auth__register-input" type="email" name="email" value="{{ old('email') }}">
        </div>
        @error('email')
        <p class="auth__error">{{ $message }}</p>
        @enderror
        <label class="auth__register-label">パスワード</label>
        <div class="auth__register-item">
            <input class="auth__register-input" type="password" name="password">
        </div>
        @error('password')
        <p class="auth__error">{{ $message }}</p>
        @enderror
        <label class="auth__register-label">パスワード確認</label>
        <div class="auth__register-item">
            <input class="auth__register-input" type="password" name="password_confirmation">
        </div>
        @error('password_confirmation')
        <p class="auth__error">{{ $message }}</p>
        @enderror
        <div class="auth__register-button">
            <button class="auth__register-button-submit" type="submit">
                登録する
            </button>
        </div>
    </div>
</form>

<div class="auth__register-login">
    <a href="{{ route('login') }}"> ログインはこちら</a>
</div>
@endsection
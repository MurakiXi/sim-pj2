@extends('layouts.app')

@section('title', 'ログイン')

@section('css')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')

<div class="auth__login-title">
    <h1>ログイン</h1>
</div>

<form action="{{ route('login') }}" class="auth__login-form" method="post" novalidate>
    @csrf
    <div class="auth__login-inner">
        <label class="auth__login-label" for="email">メールアドレス</label>
        <div class="auth__login-item">
            <input class="auth__login-input" id="email" type="email" name="email" value="{{ old('email') }}">
        </div>
        @error('email')
        <p class="auth__error">{{ $message }}</p>
        @enderror
        <label class="auth__login-label" for="password">パスワード</label>
        <div class="auth__login-item">
            <input class="auth__login-input" id="password" type="password" name="password">
        </div>
        @error('password')
        <p class="auth__error">{{ $message }}</p>
        @enderror
        <div class="auth__login-button">
            <button class="auth__login-button-submit" type="submit">
                ログインする
            </button>
        </div>
    </div>
</form>

<div class="auth__login-register">
    <a href="{{ route('register') }}">会員登録はこちら</a>
</div>

@endsection
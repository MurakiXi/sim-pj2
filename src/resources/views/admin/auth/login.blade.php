@extends('layouts.app')

@section('title', '管理者ログイン')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-login.css') }}">
@endsection

@section('content')

<div class="auth__login-title">
    <h1 class=auth__login-title-text>管理者ログイン</h1>
</div>

<form action="{{ route('admin.login.store') }}" class="auth__login-form" method="post" novalidate>
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
                管理者ログインする
            </button>
        </div>
    </div>
</form>

@endsection
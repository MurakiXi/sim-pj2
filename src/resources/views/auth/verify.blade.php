@extends('layouts.app')

@section('title','メール認証')

@section('css')
<link rel="stylesheet" href="{{ asset('css/verify.css') }}">
@endsection

@section('content')
<div class="verify__inner">
    <div class="verify__message">
        登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。
    </div>

    <div class="verify__button">
        <a class="verify__primary" href="{{ config('app.mailhog_url') }}" target="_blank" rel="noopener">
            認証はこちらから
        </a>
    </div>

    <form method="POST" action="{{ route('verification.send') }}" class="verify__resend-form">
        @csrf
        <button type="submit" class="verify__resend-mail">認証メールを再送する</button>
    </form>
</div>
@endsection
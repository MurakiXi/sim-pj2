<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'COACHTECH勤怠管理')</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/common.css') }}" />
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header__logo">
            {{-- <a href="{{ route('attendance') }}"> --}}
            <a href="">
                <img src="{{ asset('assets/images/coachtech-header-logo.png') }}" alt="COACHTECH勤怠管理">
            </a>
        </div>
        <div class="header__nav">
            {{-- <a class="header__nav-item" href="{{ route('attendance') }}"> --}}
            <a class="header__nav-item" href="">
                勤怠
            </a>
            {{-- <a class="header__nav-item-sell" href="{{ route('attendance.list') }}"> --}}
            <a class="header__nav-item" href="">
                勤怠一覧
            </a>
            {{-- <a class="header__nav-item-sell" href="{{ route('attendance.request') }}"> --}}
            <a class="header__nav-item" href="">
                申請
            </a>
            <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                @csrf
                <button class="header__nav-logout" type="submit">ログアウト</button>
            </form>
        </div>
    </header>

    <main>
        @yield('content')
    </main>
    @yield('js')
</body>

</html>
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
            <a href="{{ route('attendance') }}">
                <img src="{{ asset('assets/images/coachtech-header-logo.png') }}" alt="COACHTECH勤怠管理">
            </a>
        </div>
    </header>

    <main>
        @yield('content')
    </main>
    @yield('js')
</body>

</html>
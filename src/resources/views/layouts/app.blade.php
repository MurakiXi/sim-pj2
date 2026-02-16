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
@php
use Illuminate\Support\Facades\Auth;

$isAdmin = Auth::guard('admin')->check();
$isUser = Auth::guard('web')->check();

$hideNav = request()->routeIs([
'login',
'admin.login',
'verification.notice',
]);

$isAfterClockOut = $isUser && (($workStatus ?? null) === 'done');

$logoRoute = $isAdmin
? route('admin.attendances.index')
: route('attendances.create');

$navItems = [];

if (!$hideNav && ($isAdmin || $isUser)) {
if ($isAdmin) {
$navItems = [
['label' => '勤怠一覧', 'href' => route('admin.attendances.index')],
['label' => 'スタッフ一覧','href' => route('admin.staff.index')],
['label' => '申請一覧', 'href' => route('stamp_correction_requests.index')],
];
$logoutRoute = route('admin.logout');
} else {
if ($isAfterClockOut) {
$navItems = [
['label' => '今月の出勤一覧', 'href' => route('attendances.index')],
['label' => '申請一覧', 'href' => route('stamp_correction_requests.index')],
];
} else {
$navItems = [
['label' => '勤怠', 'href' => route('attendances.create')],
['label' => '勤怠一覧', 'href' => route('attendances.index')],
['label' => '申請', 'href' => route('stamp_correction_requests.index')],
];
}
$logoutRoute = route('logout');
}
}
@endphp

<body>
    <header class="header">
        <div class="header__logo">
            <a href="{{ $logoRoute }}">
                <img src="{{ asset('assets/images/coachtech-header-logo.png') }}" alt="COACHTECH勤怠管理">
            </a>
        </div>

        @if(!$hideNav && ($isAdmin || $isUser))
        <nav class="header__nav">
            @foreach($navItems as $item)
            <a class="header__nav-item" href="{{ $item['href'] }}">
                {{ $item['label'] }}
            </a>
            @endforeach

            <form action="{{ $logoutRoute }}" method="POST" style="display:inline;">
                @csrf
                <button class="header__nav-logout" type="submit">ログアウト</button>
            </form>
        </nav>
        @endif
    </header>


    <main>
        @yield('content')
    </main>
    @yield('js')
</body>

</html>
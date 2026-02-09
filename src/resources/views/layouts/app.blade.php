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
$isUser = Auth::guard('web')->check(); // または Auth::check()

// ナビ非表示にしたい画面（必要に応じて追加）
$hideNav = request()->routeIs([
'login',
'admin.login',
'verification.notice',
]);

// 「退勤後」判定：勤怠登録画面で $workStatus が渡っている前提
$isAfterClockOut = $isUser && (($workStatus ?? null) === 'done');

// ロゴの遷移先（管理者と一般で分ける）
$logoRoute = $isAdmin
? route('admin.attendances.index') // 実名に合わせて修正
: route('attendances.create');

// ナビ項目組み立て
$navItems = [];

if (!$hideNav && ($isAdmin || $isUser)) {
if ($isAdmin) {
$navItems = [
['label' => '勤怠一覧', 'href' => route('admin.attendances.index')], // /admin/attendance/list
['label' => 'スタッフ一覧','href' => route('admin.staff.index')], // /admin/staff/list
['label' => '申請一覧', 'href' => route('stamp_correction_requests.index')], // /stamp_correction_request/list（共通想定）
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
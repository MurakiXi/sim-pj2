@extends('layouts.app')


@section('title', '勤怠登録')

@section('css')
<link rel="stylesheet" href="{{ asset('css/create.css') }}">
@endsection

@section('content')
<div id="flash-message" class="flash-message">
    {{ session('flash_message') }}
</div>
@if (session('flash_message'))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const el = document.getElementById('flash-message');
        if (!el) return;

        el.classList.add('is-show');

        setTimeout(() => {
            el.classList.remove('is-show');
        }, 2000);
    });
</script>
@endif

<p class="create__work-status">
    {{ config("attendance.status_labels.$workStatus") ?? '不明' }}
</p>


<div id="clock" data-server-now="{{ now()->timestamp * 1000 }}">
    <div id="today-label" class="create__date-label">{{ $todayLabel }}</div>
    <div id="time-label" class="create__time-label">{{ $timeLabel }}</div>
</div>

<script src="{{ asset('js/time.js') }}" defer></script>

@if($workStatus === 'off')
<form class="create__form" method="POST" action="{{ route('attendances.clock_in') }}">
    @csrf
    <button class="create__button-clock" type="submit">出勤</button>
</form>

@elseif($workStatus === 'working')
<div class="create__btn-row">
    <form method="POST" action="{{ route('attendances.clock_out') }}">
        @csrf
        <button class="create__button-clock" type="submit">退勤</button>
    </form>

    <form method="POST" action="{{ route('attendances.break_in') }}">
        @csrf
        <button class="create__button" type="submit">休憩入</button>
    </form>

</div>

@elseif($workStatus === 'break')
<form class="create__form" method="POST" action="{{ route('attendances.break_out') }}">
    @csrf
    <button class="create__button" type="submit">休憩戻</button>
</form>

@elseif($workStatus === 'done')
<div class="create__message">お疲れ様でした。</div>
@endif


@endsection
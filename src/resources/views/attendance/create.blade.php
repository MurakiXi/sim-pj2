@extends('layouts.app')


@section('title', '勤怠登録')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user-create.css') }}">
@endsection

@section('content')
@if (session('flash_message'))
<div id="flash-message" class="create__flash-message">
    {{ session('flash_message') }}
</div>
<script>
    setTimeout(() => {
        const el = document.getElementById('flash-message');
        if (el) el.style.display = 'none';
    }, 3000);
</script>
@endif


<div class="create__work-status">
    {{ config("attendance.status_labels.$workStatus") ?? '不明' }}
</div>


<div id="clock" data-server-now="{{ now()->timestamp * 1000 }}">
    <div id="today-label">{{ $todayLabel }}</div>
    <div id="time-label">{{ $timeLabel }}</div>
</div>

<script src="{{ asset('js/time.js') }}" defer></script>



@if($workStatus === 'off')
<form method="POST" action="{{ route('attendances.clock_in') }}">
    @csrf
    <button type="submit">出勤</button>
</form>

@elseif($workStatus === 'working')
<div class="btn-row">
    <form method="POST" action="{{ route('attendances.break_in') }}">
        @csrf
        <button type="submit">休憩入</button>
    </form>

    <form method="POST" action="{{ route('attendances.clock_out') }}">
        @csrf
        <button type="submit">退勤</button>
    </form>
</div>

@elseif($workStatus === 'break')
<form method="POST" action="{{ route('attendances.break_out') }}">
    @csrf
    <button type="submit">休憩戻</button>
</form>

@elseif($workStatus === 'done')
<p>お疲れ様でした。</p>
@endif




<script src="{{ asset('js/time.js') }}" defer></script>

@endsection
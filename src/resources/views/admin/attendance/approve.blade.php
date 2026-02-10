@extends('layouts.app')

@section('title', '管理勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-approve.css') }}">
@endsection

@section('content')

<div class="show__title">勤怠詳細</div>

<table class="show__table">
    <tr class="show__table-row">
        <th class="show__table-header">名前</th>
        <td class="show__table-data" colspan="3">{{ $user->name }}</td>
    </tr>

    <tr class="show__table-row">
        <th class="show__table-header">日付</th>
        <td class="show__table-data">{{ $yearLabel }}</td>
        <td class="show__table-wave"></td>
        <td class="show__table-data">{{ $mdLabel }}</td>
    </tr>

    <tr class="show__table-row">
        <th class="show__table-header">出勤・退勤</th>
        <td class="show__table-data">{{ $displayClockInAt?->format('H:i') ?? '' }}</td>
        <td class="show__table-wave">〜</td>
        <td class="show__table-data">{{ $displayClockOutAt?->format('H:i') ?? '' }}</td>
    </tr>

    @foreach($breakRows as $break)
    <tr class="show__table-row">
        <th class="show__table-header">{{ $loop->first ? '休憩' : '休憩'.$loop->iteration }}</th>
        <td class="show__table-data">{{ $break->break_in_at?->format('H:i') ?? '' }}</td>
        <td class="show__table-wave">〜</td>
        <td class="show__table-data">{{ $break->break_out_at?->format('H:i') ?? '' }}</td>
    </tr>
    @endforeach

    <tr class="show__table-row">
        <th class="show__table-header">備考</th>
        <td class="show__table-data" colspan="3">{{ $displayNote ?? '' }}</td>
    </tr>
</table>

@if($canApprove)
<div class="show__table-button-approve">
    <form method="POST" action="{{ route('admin.attendances.approve', $latestRequest->id) }}">
        @csrf
        @method('PATCH')
        <button type="submit">承認</button>
    </form>
</div>
@elseif($isApproved)
<div class="show__table-button-approved">
    <button type="button" disabled>承認済み</button>
</div>
@endif


@endsection
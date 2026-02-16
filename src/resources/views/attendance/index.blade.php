@extends('layouts.app')

@section('title', '勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user-index.css') }}">
@endsection

@section('content')

<div class="index__title">勤怠一覧</div>

<div class="index__header">
    <a class="index__month-nav" href="{{ route('attendances.index', ['month' => $prevMonth]) }}">←前月</a>

    <div class="index__month-label">
        {{ $monthLabel }}
    </div>

    <a class="index__month-nav" href="{{ route('attendances.index', ['month' => $nextMonth]) }}">翌月→</a>
</div>


<table class="index__table">
    <tr class="index__table-row-header">
        <th class="index__table-header">日付</th>
        <th class="index__table-header">出勤</th>
        <th class="index__table-header">退勤</th>
        <th class="index__table-header">休憩</th>
        <th class="index__table-header">合計</th>
        <th class="index__table-header">詳細</th>
    </tr>
    @foreach($rows as $row)
    <tr class="index__table-row">
        <td class="index__table-item">{{ $row['date'] }}({{ $row['weekday'] }})</td>
        <td class="index__table-item">{{ $row['clock_in'] }}</td>
        <td class="index__table-item">{{ $row['clock_out'] }}</td>
        <td class="index__table-item">{{ $row['break'] }}</td>
        <td class="index__table-item">{{ $row['work'] }}</td>
        <td class="index__table-item">
            @if($row['id'])
            <a class="index__table-item-detail" href="{{ route('attendances.show', $row['id']) }}">詳細</a>
            @endif
        </td>
    </tr>
    @endforeach

</table>

@endsection
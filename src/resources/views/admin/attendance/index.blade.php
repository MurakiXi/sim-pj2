@extends('layouts.app')

@section('title', '管理勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-index.css') }}">
@endsection

@section('content')

<div class="index__title">{{ $titleDate }}の勤怠</div>

<div class="index__header">
    <a class="index__date-nav" href="{{ route('admin.attendances.index', ['date' => $prevDate]) }}">←前日</a>

    <div class="index__date-label">
        {{ $dateLabel }}
    </div>

    <a class="index__date-nav" href="{{ route('admin.attendances.index', ['date' => $nextDate]) }}">翌日→</a>
</div>

<table class="index__table">
    <tr class="index__table-row">
        <th class="index__table-header">名前</th>
        <th class="index__table-header">出勤</th>
        <th class="index__table-header">退勤</th>
        <th class="index__table-header">休憩</th>
        <th class="index__table-header">合計</th>
        <th class="index__table-header">詳細</th>
    </tr>

    @foreach($rows as $row)
    <tr class="index__table-row">
        <td class="index__table-item">{{ $row['name'] }}</td>
        <td class="index__table-item">{{ $row['clock_in'] }}</td>
        <td class="index__table-item">{{ $row['clock_out'] }}</td>
        <td class="index__table-item">{{ $row['break'] }}</td>
        <td class="index__table-item">{{ $row['work'] }}</td>
        <td class="index__table-item">
            @if($row['id'])
            <a href="{{ route('admin.attendances.show', $row['id']) }}">詳細</a>
            @else

            @endif
        </td>
    </tr>
    @endforeach
</table>

@endsection
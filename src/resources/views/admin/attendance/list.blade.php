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
        <form method="GET" action="{{ route('admin.attendances.index') }}" class="index__date-form">
            <button type="button" id="date-picker-trigger" class="index__date-trigger" aria-label="日付を選択">
                <svg class="index__date-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M7 2v2M17 2v2M4 6h16M5 8h14v13H5z"
                        fill="none" stroke="currentColor" stroke-width="2" />
                </svg>
            </button>

            <input
                type="date"
                id="date-picker"
                name="date"
                value="{{ $dateLabel }}"
                class="index__date-input">

            <span class="index__date-text">{{ $dateLabel }}</span>
        </form>
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
            <a class="index__table-item-detail" href="{{ route('admin.attendances.show', $row['id']) }}">詳細</a>
            @else

            @endif
        </td>
    </tr>
    @endforeach
</table>

@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('date-picker');
        const trigger = document.getElementById('date-picker-trigger');
        if (!input || !trigger) return;

        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            if (typeof input.showPicker === 'function') {
                input.showPicker();
            } else {
                input.focus();
                input.click();
            }
        });

        input.addEventListener('change', () => {
            if (input.form) input.form.submit();
        });
    });
</script>
@endsection
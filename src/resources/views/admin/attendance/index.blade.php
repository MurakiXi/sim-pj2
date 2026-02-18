@extends('layouts.app')

@section('title', 'スタッフ別勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user-index.css') }}">
@endsection

@section('content')

<div class="index__title">{{ $name }}さんの勤怠</div>

<div class="index__header">
    <a class="index__month-nav"
        href="{{ route('admin.staff.attendances.index', ['user' => $userId, 'month' => $prevMonth]) }}">
        ←前月
    </a>

    <div class="index__month-label">
        <form method="GET" action="{{ route('admin.attendances.index') }}" class="index__month-form">
            <button type="button" id="month-picker-trigger" class="index__month-trigger" aria-label="月を選択">
                <svg class="index__month-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M7 2v2M17 2v2M4 6h16M5 8h14v13H5z"
                        fill="none" stroke="currentColor" stroke-width="2" />
                </svg>
            </button>

            <input
                type="month"
                id="month-picker"
                name="month"
                value="{{ $monthValue }}"
                class="index__month-input">

            <span class="index__month-text">{{ $monthLabel }}</span>
        </form>
    </div>


    <a class="index__month-nav"
        href="{{ route('admin.staff.attendances.index', ['user' => $userId, 'month' => $nextMonth]) }}">
        翌月→
    </a>
</div>

<table class="index__table">
    <tr class="index__table-row">
        <th class="index__table-header">日付</th>
        <th class="index__table-header">出勤</th>
        <th class="index__table-header">退勤</th>
        <th class="index__table-header">休憩</th>
        <th class="index__table-header">合計</th>
        <th class="index__table-header">詳細</th>
    </tr>

    @foreach($rows as $row)
    <tr class="index__table-row">
        <td class="index__table-item">{{ $row['date'] }}（{{ $row['weekday'] }}）</td>
        <td class="index__table-item">{{ $row['clock_in'] }}</td>
        <td class="index__table-item">{{ $row['clock_out'] }}</td>
        <td class="index__table-item">{{ $row['break'] }}</td>
        <td class="index__table-item">{{ $row['work'] }}</td>
        <td class="index__table-item">
            <a class="index__table-item-detail"
                href="{{ !empty($row['id'])
        ? route('admin.attendances.show', $row['id'])
        : route('admin.staff.attendances.show_by_date', ['user' => $userId, 'date' => $row['work_date']]) }}">
                詳細
            </a>
        </td>

    </tr>
    @endforeach
</table>

<div class="index__footer">
    <a class="index__csv-btn"
        href="{{ route('admin.staff.attendances.csv', ['user' => $userId, 'month' => $currentMonth]) }}">
        CSV出力
    </a>
</div>

@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('month-picker');
        const trigger = document.getElementById('month-picker-trigger');
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
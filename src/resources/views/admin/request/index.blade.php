@extends('layouts.app')

@section('title', '修正申請一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/request-index.css') }}">
@endsection

@section('content')

<div class="index__title">申請一覧</div>

<div class="index__header">
    <a href="{{ route('admin.stamp_correction_requests.index', ['tab' => 'awaiting']) }}"
        class="{{ $tab === 'awaiting' ? 'is-active' : '' }}">
        承認待ち
    </a>
    <a href="{{ route('admin.stamp_correction_requests.index', ['tab' => 'approved']) }}"
        class="{{ $tab === 'approved' ? 'is-active' : '' }}">
        承認済み
    </a>
</div>

<table class="index__table">
    <tr class="index__table-row">
        <th class="index__table-header">状態</th>
        <th class="index__table-header">名前</th>
        <th class="index__table-header">対象日時</th>
        <th class="index__table-header">申請理由</th>
        <th class="index__table-header">申請日時</th>
        <th class="index__table-header">詳細</th>
    </tr>

    @foreach($rows as $row)
    <tr class="index__table-row">
        <td class="index__table-item">{{ $row['status_label'] }}</td>
        <td class="index__table-item">{{ $row['name'] }}</td>
        <td class="index__table-item">{{ $row['target_date'] }}</td>
        <td class="index__table-item">{{ $row['requested_note'] }}</td>
        <td class="index__table-item">{{ $row['applied_at'] }}</td>
        <td class="index__table-item">
            <a href="{{ route('admin.requests.show', $row['id']) }}">詳細</a>
        </td>
    </tr>
    @endforeach
</table>

@endsection
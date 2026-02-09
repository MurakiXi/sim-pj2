@extends('layouts.app')

@section('title', '修正申請一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/request-index.css') }}">
@endsection

@section('content')

<div class="index__title">申請一覧</div>

<div class="index__header">
    <a href="{{ route('stamp_correction_requests.index', ['status' => 'awaiting_approval']) }}"
        class="{{ ($status ?? '') === 'awaiting_approval' ? 'is-active' : '' }}">
        承認待ち
    </a>

    <a href="{{ route('stamp_correction_requests.index', ['status' => 'approved']) }}"
        class="{{ ($status ?? '') === 'approved' ? 'is-active' : '' }}">
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

    @foreach($requests as $req)
    <tr class="index__table-row">
        <td class="index__table-item">
            {{ $req->status === 'awaiting_approval' ? '承認待ち' : '承認済み' }}
        </td>
        <td class="index__table-item">{{ $req->attendance->user->name }}</td>
        <td class="index__table-item">{{ optional($req->attendance->work_date)->format('Y/m/d') }}</td>
        <td class="index__table-item">{{ $req->requested_note }}</td>
        <td class="index__table-item">{{ optional($req->created_at)->format('Y/m/d') }}</td>
        <td class="index__table-item">
            <a href="{{ route('admin.requests.show', $req->id) }}">詳細</a>
        </td>
    </tr>
    @endforeach
    {{ $requests->links() }}

</table>

@endsection
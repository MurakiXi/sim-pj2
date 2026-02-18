@extends('layouts.app')

@section('title', 'スタッフ一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/staff.css') }}">
@endsection

@section('content')

<div class="index__title">スタッフ一覧</div>

<table class="index__table">
    <tr class="index__table-row">
        <th class="index__table-header">名前</th>
        <th class="index__table-header">メールアドレス</th>
        <th class="index__table-header">月次勤怠</th>
    </tr>

    @forelse($users as $user)
    <tr class="index__table-row">
        <td class="index__table-item">{{ $user->name }}</td>
        <td class="index__table-item">{{ $user->email }}</td>
        <td class="index__table-item">
            <a class="index__table-item-detail" href="{{ route('admin.staff.attendances.index', ['user' => $user->id]) }}">詳細</a>
        </td>
    </tr>
    @empty
    <tr>
        <td colspan="3">スタッフがいません</td>
    </tr>
    @endforelse

</table>
{{ $users->links() }}
@endsection
@extends('layouts.app')

@section('title', '管理勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-show.css') }}">
@endsection

@section('content')

<div class="show__title">勤怠詳細</div>

<form method="POST" action="{{ route('admin.attendances.update', $attendance->id) }}" class="show__form">
    @csrf
    @method('PATCH')
    <table class="show__table">
        <tr class="show__table-row">
            <th class="show__table-header">名前</th>
            <td class="show__table-data" colspan="3">
                {{ $user->name }}
            </td>
        </tr>

        <tr class="show__table-row">
            <th class="show__table-header">日付</th>
            <td class="show__table-data">{{ $yearLabel }}</td>
            <td class="show__table-wave"></td>
            <td class="show__table-data">{{ $mdLabel }}</td>
        </tr>

        <tr class="show__table-row">
            <th class="show__table-header">出勤・退勤</th>
            <td class="show__table-data">
                <input type="time" name="clock_in_at"
                    value="{{ old('clock_in_at', $attendance->clock_in_at?->format('H:i') ?? '') }}" @disabled($hasAwaitingApproval)>
            </td>
            <td class="show__table-wave">〜</td>
            <td class="show__table-data">
                <input type="time" name="clock_out_at"
                    value="{{ old('clock_out_at', $attendance->clock_out_at?->format('H:i') ?? '') }}" @disabled($hasAwaitingApproval)>
            </td>
            <td class="show__table-data">
                @if($errors->has('clock_in_at') || $errors->has('clock_out_at'))
                <p class="form__error">
                    {{ $errors->first('clock_in_at') ?: $errors->first('clock_out_at') }}
                </p>
                @endif

            </td>
        </tr>

        @foreach($breakRows as $break)
        <tr class="show__table-row">
            <th class="show__table-header">
                {{ $loop->first ? '休憩' : '休憩'.$loop->iteration }}
            </th>
            <td class="show__table-data">
                <input type="time"
                    name="breaks[{{ $loop->index }}][break_in_at]"
                    value="{{ old("breaks.$loop->index.break_in_at", $break->break_in_at?->format('H:i') ?? '') }}" @disabled($hasAwaitingApproval)>
            </td>
            <td class="show__table-wave">〜</td>
            <td class="show__table-data">
                <input type="time"
                    name="breaks[{{ $loop->index }}][break_out_at]"
                    value="{{ old("breaks.$loop->index.break_out_at", $break->break_out_at?->format('H:i') ?? '') }}" @disabled($hasAwaitingApproval)>
            </td>
            <td class="show__table-data">
                @if(
                $errors->has("breaks.$loop->index.break_in_at") ||
                $errors->has("breaks.$loop->index.break_out_at")
                )
                <p class="form__error">
                    {{ $errors->first("breaks.$loop->index.break_in_at") ?: $errors->first("breaks.$loop->index.break_out_at") }}
                </p>
                @endif

            </td>
        </tr>
        @endforeach

        <tr class="show__table-row">
            <th class="show__table-header">備考</th>
            <td class="show__table-data" colspan="3">
                <textarea name="note" class="show__table-note" @disabled($hasAwaitingApproval)>{{ old('note', $attendance->note) }}</textarea>
            </td>
            <td class="show__table-data">
                @error("note")
                <p class="form__error">{{ $message }}</p>
                @enderror
            </td>
        </tr>
    </table>

    <div class="show__form-button">
        @if($hasAwaitingApproval)
        <p class="show__form-button-message">※承認待ちのため修正はできません。</p>
        @else
        <button type="submit" class="show__form-button-submit">修正</button>
        @endif
    </div>
</form>

@endsection
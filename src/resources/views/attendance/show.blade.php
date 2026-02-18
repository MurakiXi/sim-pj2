@extends('layouts.app')

@section('title', '勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user-show.css') }}">
@endsection

@section('content')

<div class="show__title">勤怠詳細</div>

<form method="POST" action="{{ route('attendances.update', $attendance->id) }}" class="show__form">
    @csrf
    @method('PATCH')
    <table class="show__table">
        <colgroup>
            <col class="show__col-header">
            <col class="show__col-from">
            <col class="show__col-wave">
            <col class="show__col-to">
        </colgroup>
        <tr class="show__table-row">
            <th class="show__table-header">名前</th>
            <td class="show__table-data show__table-data--wide" colspan="3">{{ $user->name }}</td>
        </tr>

        <tr class="show__table-row">
            <th class="show__table-header">日付</th>
            <td class="show__table-data">{{ $yearLabel }}</td>
            <td class="show__table-wave"></td>
            <td class="show__table-data">{{ $mdLabel }}</td>
        </tr>

        <tr class="show__table-row">
            <th class="show__table-header">出勤・退勤</th>

            <td class="show__table-data show__table-data--field">
                <input type="text" class="show__table-input" name="clock_in_at"
                    value="{{ old('clock_in_at', $attendance->clock_in_at?->format('H:i') ?? '') }}"
                    @disabled($hasAwaitingApproval)>

                @error('clock_in_at')
                <p class="form__error">{{ $message }}</p>
                @enderror
            </td>

            <td class="show__table-wave">〜</td>

            <td class="show__table-data show__table-data--field">
                <input type="text" class="show__table-input" name="clock_out_at"
                    value="{{ old('clock_out_at', $attendance->clock_out_at?->format('H:i') ?? '') }}"
                    @disabled($hasAwaitingApproval)>

                @error('clock_out_at')
                <p class="form__error">{{ $message }}</p>
                @enderror
            </td>
        </tr>


        @foreach($breakRows as $break)
        <tr class="show__table-row">
            <th class="show__table-header">
                {{ $loop->first ? '休憩' : '休憩'.$loop->iteration }}
            </th>
            <td class="show__table-data">
                <input type="text" class="show__table-input"
                    name="breaks[{{ $loop->index }}][break_in_at]"
                    value="{{ old("breaks.$loop->index.break_in_at", $break->break_in_at?->format('H:i') ?? '') }}" @disabled($hasAwaitingApproval)>
            </td>
            <td class="show__table-wave">〜</td>
            <td class="show__table-data">
                <input type="text" class="show__table-input"
                    name="breaks[{{ $loop->index }}][break_out_at]"
                    value="{{ old("breaks.$loop->index.break_out_at", $break->break_out_at?->format('H:i') ?? '') }}" @disabled($hasAwaitingApproval)>
            </td>
            @error("breaks.$loop->index.break_in_at")
            <p class="form__error">{{ $message }}</p>
            @enderror
        </tr>
        @endforeach

        <tr class="show__table-row">
            <th class="show__table-header">備考</th>
            <td class="show__table-data show__table-data--note" colspan="3">
                <textarea name="note" class="show__table-note" @disabled($hasAwaitingApproval)>{{ old('note', $attendance->note) }}</textarea>

                @error('note')
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
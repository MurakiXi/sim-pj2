@extends('layouts.app')

@section('title', '管理勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-show.css') }}">
@endsection

@section('content')
<div id="flash-message" class="flash-message">
    {{ session('flash_message') }}
</div>
@if (session('flash_message'))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const el = document.getElementById('flash-message');
        if (!el) return;

        el.classList.add('is-show');

        setTimeout(() => {
            el.classList.remove('is-show');
        }, 2000);
    });
</script>
@endif

<div class="show__title">勤怠詳細</div>

<form method="POST" action="{{ route('admin.attendances.update', $attendance->id) }}" class="show__form">
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
            <td class="show__table-data show__table-data--md">{{ $mdLabel }}</td>
        </tr>

        <tr class="show__table-row">
            <th class="show__table-header">出勤・退勤</th>

            <td class="show__table-data show__table-data--field">
                <input type="text" class="show__table-input" name="clock_in_at"
                    value="{{ old('clock_in_at', $attendance->clock_in_at?->format('H:i') ?? '') }}"
                    @disabled($hasAwaitingApproval)>
            </td>

            <td class="show__table-wave">〜</td>

            <td class="show__table-data show__table-data--field show__table-data--with-error">
                @php
                $clockError = $errors->first('clock_in_at') ?: $errors->first('clock_out_at');
                @endphp

                <div class="show__field-with-error">
                    <input type="text" class="show__table-input" name="clock_out_at"
                        value="{{ old('clock_out_at', $attendance->clock_out_at?->format('H:i') ?? '') }}"
                        @disabled($hasAwaitingApproval)>

                    @if($clockError)
                    <p class="form__error show__error-inline">{{ $clockError }}</p>
                    @endif
                </div>
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
                    value="{{ old("breaks.$loop->index.break_in_at", $break->break_in_at?->format('H:i') ?? '') }}"
                    @disabled($hasAwaitingApproval)>
            </td>

            <td class="show__table-wave">〜</td>

            <td class="show__table-data show__table-data--with-error">
                @php
                $breakInKey = "breaks.$loop->index.break_in_at";
                $breakOutKey = "breaks.$loop->index.break_out_at";
                $breakError = $errors->first($breakInKey) ?: $errors->first($breakOutKey);
                @endphp

                <div class="show__field-with-error">
                    <input type="text" class="show__table-input"
                        name="breaks[{{ $loop->index }}][break_out_at]"
                        value="{{ old("breaks.$loop->index.break_out_at", $break->break_out_at?->format('H:i') ?? '') }}"
                        @disabled($hasAwaitingApproval)>

                    @if($breakError)
                    <p class="form__error show__error-inline">{{ $breakError }}</p>
                    @endif
                </div>
            </td>
        </tr>
        @endforeach

        <tr class="show__table-row show__table-row--note">
            <th class="show__table-header">備考</th>

            <td class="show__table-data show__table-data--note" colspan="3">
                <div class="show__note-layout">
                    <textarea name="note" class="show__table-note" @disabled($hasAwaitingApproval)>{{ old('note', $attendance->note) }}</textarea>

                    @error('note')
                    <div class="show__error-slot">
                        <p class="form__error show__error-inline">{{ $message }}</p>
                    </div>
                    @enderror
                </div>
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
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'clock_in_at'  => ['required', 'date_format:H:i'],
            'clock_out_at' => ['required', 'date_format:H:i'],
            'note'         => ['required', 'string', 'max:255'],

            'breaks'                => ['nullable', 'array'],
            'breaks.*.break_in_at'  => ['nullable', 'date_format:H:i', 'required_with:breaks.*.break_out_at'],
            'breaks.*.break_out_at' => ['nullable', 'date_format:H:i', 'required_with:breaks.*.break_in_at'],
        ];
    }


    public function messages(): array
    {
        return [
            'clock_in_at.required'   => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out_at.required'  => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_in_at.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out_at.date_format' => '出勤時間もしくは退勤時間が不適切な値です',

            'breaks.*.break_in_at.date_format'  => '休憩時間が不適切な値です',
            'breaks.*.break_out_at.date_format' => '休憩時間が不適切な値です',
            'breaks.*.break_in_at.required_with' => '休憩時間が不適切な値です',
            'breaks.*.break_out_at.required_with' => '休憩時間が不適切な値です',

            'note.required' => '備考を記入してください',
            'note.max'      => '備考を記入してください',
        ];
    }
}

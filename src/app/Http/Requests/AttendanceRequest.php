<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
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

            'breaks'                 => ['array'],
            'breaks.*.break_in_at'   => ['nullable', 'date_format:H:i'],
            'breaks.*.break_out_at'  => ['nullable', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => '備考を記入してください',

        ];
    }

    public function withValidator($validator): void
    {

        $validator->after(function ($v) {
            $in  = $this->input('clock_in_at');
            $out = $this->input('clock_out_at');
            $toMinutes = function (?string $t): ?int {
                if (!$t) return null;
                return ((int)substr($t, 0, 2)) * 60 + (int)substr($t, 3, 2);
            };

            if ($in && $out && $in > $out) {
                $v->errors()->add('clock_in_at', '出勤時間もしくは退勤時間が不適切な値です');
            }

            foreach ((array)$this->input('breaks', []) as $i => $b) {
                $bin  = $b['break_in_at']  ?? null;
                $bout = $b['break_out_at'] ?? null;

                if (!$bin && !$bout) continue;

                if (!$bin || !$bout) {
                    $v->errors()->add("breaks.$i.break_in_at", '休憩時間が不適切な値です');
                    continue;
                }

                if ($bin >= $bout) {
                    $v->errors()->add("breaks.$i.break_in_at", '休憩時間が不適切な値です');
                    continue;
                }

                $outMin  = $toMinutes($out);
                $binMin  = $toMinutes($bin);
                $boutMin = $toMinutes($bout);

                if ($outMin !== null && $boutMin !== null && $boutMin > $outMin) {
                    $v->errors()->add("breaks.$i.break_out_at", '休憩時間もしくは退勤時間が不適切な値です');
                }

                if ($outMin !== null && $binMin !== null && $binMin > $outMin) {
                    $v->errors()->add("breaks.$i.break_in_at", '休憩時間もしくは退勤時間が不適切な値です');
                }
            }
        });
    }
}

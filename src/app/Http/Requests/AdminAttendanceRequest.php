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

            'clock_in_at.required'     => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out_at.required'    => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_in_at.date_format'  => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out_at.date_format' => '出勤時間もしくは退勤時間が不適切な値です',

            'breaks.*.break_in_at.date_format'  => '休憩時間が不適切な値です',
            'breaks.*.break_out_at.date_format' => '休憩時間が不適切な値です',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {

            if ($v->errors()->has('clock_in_at') || $v->errors()->has('clock_out_at')) {
                return;
            }

            $toMinutes = function (?string $t): ?int {
                if (!is_string($t)) return null;
                if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t)) return null;
                return ((int)substr($t, 0, 2)) * 60 + (int)substr($t, 3, 2);
            };

            $in  = $this->input('clock_in_at');
            $out = $this->input('clock_out_at');

            $inMin  = $toMinutes($in);
            $outMin = $toMinutes($out);

            if ($inMin !== null && $outMin !== null && $inMin > $outMin) {
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

                if (!$toMinutes($bin) || !$toMinutes($bout)) {
                    continue;
                }

                $binMin  = $toMinutes($bin);
                $boutMin = $toMinutes($bout);

                if ($binMin === null || $boutMin === null || $binMin >= $boutMin) {
                    $v->errors()->add("breaks.$i.break_in_at", '休憩時間が不適切な値です');
                    continue;
                }

                if ($inMin !== null && $binMin < $inMin) {
                    $v->errors()->add("breaks.$i.break_in_at", '休憩時間が不適切な値です');
                }
                if ($outMin !== null && $boutMin > $outMin) {
                    $v->errors()->add("breaks.$i.break_in_at", '休憩時間が不適切な値です');
                }
            }
        });
    }
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach (['clock_in_at', 'clock_out_at'] as $k) {
            if (array_key_exists($k, $data) && is_string($data[$k])) {
                $data[$k] = trim($data[$k]);
                if ($data[$k] === '') $data[$k] = null;
            }
        }

        foreach (($data['breaks'] ?? []) as $i => $b) {
            foreach (['break_in_at', 'break_out_at'] as $k) {
                if (array_key_exists($k, $b) && is_string($b[$k])) {
                    $data['breaks'][$i][$k] = trim($b[$k]);
                    if ($data['breaks'][$i][$k] === '') $data['breaks'][$i][$k] = null;
                }
            }
        }

        $this->replace($data);
    }
}

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
            'breaks'                => ['array'],
            'breaks.*.break_in_at'  => ['nullable', 'date_format:H:i'],
            'breaks.*.break_out_at' => ['nullable', 'date_format:H:i'],
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

            $toMin = function (?string $t): ?int {
                if (!$t) return null;
                $h = (int) substr($t, 0, 2);
                $m = (int) substr($t, 3, 2);
                return $h * 60 + $m;
            };

            $inStr  = $this->input('clock_in_at');
            $outStr = $this->input('clock_out_at');

            $in  = $toMin($inStr);
            $out = $toMin($outStr);

            if (!is_null($in) && !is_null($out) && $in > $out) {
                $v->errors()->add('clock_in_at', '出勤時間もしくは退勤時間が不適切な値です');
            }

            $breaks = collect((array) $this->input('breaks', []))
                ->map(function ($b, $i) use ($toMin) {
                    $binStr  = $b['break_in_at'] ?? null;
                    $boutStr = $b['break_out_at'] ?? null;

                    $bin  = $toMin($binStr);
                    $bout = $toMin($boutStr);

                    return [
                        'i'       => $i,
                        'bin_str' => $binStr,
                        'bout_str' => $boutStr,
                        'bin'     => $bin,
                        'bout'    => $bout,
                    ];
                });

            foreach ($breaks as $b) {
                $hasAny = filled($b['bin_str']) || filled($b['bout_str']);
                if (!$hasAny) continue;

                if (!filled($b['bin_str']) || !filled($b['bout_str'])) {
                    $v->errors()->add("breaks.{$b['i']}.break_in_at", '休憩時間が不適切な値です');
                    continue;
                }

                if (!is_null($b['bin']) && !is_null($b['bout']) && $b['bin'] >= $b['bout']) {
                    $v->errors()->add("breaks.{$b['i']}.break_in_at", '休憩時間が不適切な値です');
                    continue;
                }

                if (!is_null($out) && !is_null($b['bin']) && $b['bin'] > $out) {
                    $v->errors()->add("breaks.{$b['i']}.break_in_at", '休憩時間が不適切な値です');
                    continue;
                }
                if (!is_null($out) && !is_null($b['bout']) && $b['bout'] > $out) {

                    $v->errors()->add("breaks.{$b['i']}.break_in_at", '休憩時間が不適切な値です');
                    continue;
                }
            }

            $validBreaks = $breaks
                ->filter(fn($b) => filled($b['bin_str']) && filled($b['bout_str']) && !is_null($b['bin']) && !is_null($b['bout']) && $b['bin'] < $b['bout'])
                ->sortBy('bin')
                ->values();

            for ($k = 0; $k < $validBreaks->count() - 1; $k++) {
                $cur  = $validBreaks[$k];
                $next = $validBreaks[$k + 1];

                if ($cur['bout'] > $next['bin']) {
                    $v->errors()->add("breaks.{$next['i']}.break_in_at", '休憩時間が不適切な値です');
                }
            }
        });
    }
}

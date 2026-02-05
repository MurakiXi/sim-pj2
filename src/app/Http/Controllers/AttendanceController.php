<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Requests\AttendanceRequest;

class AttendanceController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();
        $today = now()->toDateString();
        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();
        $workStatus = $this->determineStatus($attendance);
        return view('attendance.create', [
            'attendance' => $attendance,
            'workStatus' => $workStatus,
            'todayLabel' => now()->format('Y年n月j日'),
            'timeLabel' => now()->format('H:i')
        ]);
    }

    private function determineStatus(?Attendance $attendance): string
    {
        if (!$attendance) {
            return 'off';
        }
        if (is_null($attendance->clock_in_at)) {
            return 'off';
        }
        if (!is_null($attendance->clock_out_at)) {
            return 'done';
        }
        $hasOpenBreak = $attendance->breaktimes()->whereNull('break_out_at')->exists();
        if ($hasOpenBreak) {
            return 'break';
        }
        return 'working';
    }

    private function findAttendanceForUpdate(int $userId, string $workDate): ?Attendance
    {
        return Attendance::query()
            ->where('user_id', $userId)
            ->where('work_date', $workDate)
            ->lockForUpdate()
            ->first();
    }

    public function clockIn(Request $request)
    {
        $userId = (int) $request->user()->id;
        $today  = now()->toDateString();

        $result = DB::transaction(function () use ($userId, $today) {
            $attendance = $this->findAttendanceForUpdate($userId, $today);

            if (!$attendance) {
                $attendance = Attendance::create([
                    'user_id'     => $userId,
                    'work_date'   => $today,
                    'clock_in_at' => now(),
                ]);

                return ['status' => 'ok', 'time' => $attendance->clock_in_at->format('H:i')];
            }

            if (!is_null($attendance->clock_out_at)) {
                return ['status' => 'already_clocked_out', 'time' => $attendance->clock_out_at->format('H:i')];
            }

            if (!is_null($attendance->clock_in_at)) {
                return ['status' => 'already_clocked_in', 'time' => $attendance->clock_in_at->format('H:i')];
            }

            $attendance->clock_in_at = now();
            $attendance->save();

            return ['status' => 'ok', 'time' => $attendance->clock_in_at->format('H:i')];
        });

        return redirect()
            ->route('attendances.create')
            ->with('flash_message', match ($result['status']) {
                'ok'                  => "出勤を記録しました：{$result['time']}",
                'already_clocked_in'  => "既に出勤済みです：{$result['time']}",
                'already_clocked_out' => "既に退勤済みです：{$result['time']}",
                default               => '処理に失敗しました。',
            });
    }

    public function clockOut(Request $request)
    {
        $userId = (int) $request->user()->id;
        $today  = now()->toDateString();

        $result = DB::transaction(function () use ($userId, $today) {
            $attendance = $this->findAttendanceForUpdate($userId, $today);

            if (!$attendance || is_null($attendance->clock_in_at)) {
                return ['status' => 'no_clock_in'];
            }

            if (!is_null($attendance->clock_out_at)) {
                return ['status' => 'already_clocked_out', 'time' => $attendance->clock_out_at->format('H:i')];
            }

            $hasOpenBreak = $attendance->breaktimes()
                ->whereNull('break_out_at')
                ->exists();

            if ($hasOpenBreak) {
                return ['status' => 'break_open'];
            }

            $attendance->clock_out_at = now();
            $attendance->save();

            return ['status' => 'ok', 'time' => $attendance->clock_out_at->format('H:i')];
        });

        return redirect()
            ->route('attendances.create')
            ->with('flash_message', match ($result['status']) {
                'ok'                 => "退勤を記録しました：{$result['time']}",
                'no_clock_in'        => '出勤していないため、退勤できません。',
                'break_open'         => '休憩中のため、先に休憩終了してください。',
                'already_clocked_out' => "既に退勤済みです：{$result['time']}",
                default              => '処理に失敗しました。',
            });
    }

    public function breakIn(Request $request)
    {
        $userId = (int) $request->user()->id;
        $today  = now()->toDateString();

        $result = DB::transaction(function () use ($userId, $today) {
            $attendance = $this->findAttendanceForUpdate($userId, $today);

            if (!$attendance || is_null($attendance->clock_in_at)) {
                return ['status' => 'no_clock_in'];
            }

            if (!is_null($attendance->clock_out_at)) {
                return ['status' => 'already_clocked_out'];
            }

            $hasOpenBreak = $attendance->breaktimes()
                ->whereNull('break_out_at')
                ->exists();

            if ($hasOpenBreak) {
                return ['status' => 'break_already_open'];
            }

            $break = $attendance->breaktimes()->create([
                'break_in_at'  => now(),
                'break_out_at' => null,
            ]);

            return ['status' => 'ok', 'time' => $break->break_in_at->format('H:i')];
        });

        return redirect()
            ->route('attendances.create')
            ->with('flash_message', match ($result['status']) {
                'ok'               => "休憩に入りました：{$result['time']}",
                'no_clock_in'      => '出勤していないため、休憩に入れません。',
                'already_clocked_out' => '退勤後のため、休憩に入れません。',
                'break_already_open'  => 'すでに休憩中です。',
                default            => '処理に失敗しました。',
            });
    }

    public function breakOut(Request $request)
    {
        $userId = (int) $request->user()->id;
        $today  = now()->toDateString();

        $result = DB::transaction(function () use ($userId, $today) {
            $attendance = $this->findAttendanceForUpdate($userId, $today);

            if (!$attendance || is_null($attendance->clock_in_at)) {
                return ['status' => 'no_clock_in'];
            }

            if (!is_null($attendance->clock_out_at)) {
                return ['status' => 'already_clocked_out'];
            }

            $break = $attendance->breaktimes()
                ->whereNull('break_out_at')
                ->orderByDesc('break_in_at')
                ->lockForUpdate()
                ->first();

            if (!$break) {
                return ['status' => 'break_not_open'];
            }

            $break->break_out_at = now();
            $break->save();

            return ['status' => 'ok', 'time' => $break->break_out_at->format('H:i')];
        });

        return redirect()
            ->route('attendances.create')
            ->with('flash_message', match ($result['status']) {
                'ok'                 => "休憩を終了しました：{$result['time']}",
                'no_clock_in'        => '出勤していないため、休憩を終了できません。',
                'already_clocked_out' => '退勤後のため、休憩を終了できません。',
                'break_not_open'     => '休憩中ではないため、休憩を終了できません。',
                default              => '処理に失敗しました。',
            });
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $monthParam = (string) $request->query('month', now()->format('Y-m'));
        $current = Carbon::createFromFormat('Y-m', $monthParam) ?: now();
        $current = $current->startOfMonth();

        $start = $current->copy()->startOfMonth();
        $end   = $current->copy()->endOfMonth();

        $attendances = Attendance::with(['breakTimes' => fn($q) => $q->orderBy('break_in_at')])
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $attendanceByDate = $attendances->keyBy(fn($a) => $a->work_date->toDateString());

        $rows = collect();
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            /** @var Attendance|null $a */
            $a = $attendanceByDate->get($d->toDateString());

            $rows->push([
                'id'         => $a?->id,
                'date'       => $d->format('m/d'),
                'weekday'    => ['日', '月', '火', '水', '木', '金', '土'][$d->dayOfWeek],
                'clock_in'   => $a?->clock_in_at?->format('H:i') ?? '',
                'clock_out'  => $a?->clock_out_at?->format('H:i') ?? '',
                'break'      => ($a && $a->clock_out_at) ? $a->breakDurationLabel() : '',
                'work'       => ($a && $a->clock_out_at) ? $a->workDurationLabel() : '',
            ]);
        }

        return view('attendance.index', [
            'rows'       => $rows,
            'monthLabel' => $current->format('Y/m'),
            'prevMonth'  => $current->copy()->subMonth()->format('Y-m'),
            'nextMonth'  => $current->copy()->addMonth()->format('Y-m'),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();

        $attendance = Attendance::with(['breakTimes' => fn($q) => $q->orderBy('break_in_at')])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // 休憩回数分 + 追加1行
        $breakRows = $attendance->breakTimes->concat([new BreakTime()]);

        return view('attendance.show', [
            'user'       => $user,
            'attendance' => $attendance,
            'yearLabel'  => $attendance->work_date->format('Y年'),
            'mdLabel'    => $attendance->work_date->format('n月j日'),
            'clockIn'    => $attendance->clock_in_at?->format('H:i') ?? '',
            'clockOut'   => $attendance->clock_out_at?->format('H:i') ?? '',
            'breakRows'  => $breakRows,
            'note'       => $attendance->note ?? '',
        ]);
    }


    private function formatDuration(?int $seconds): string
    {
        if ($seconds === null || $seconds < 0) {
            return '';
        }
        $hours = intdiv($seconds, 3600);
        $mins  = intdiv($seconds % 3600, 60);

        return sprintf('%02d:%02d', $hours, $mins);
    }

    public function update(AttendanceRequest $request, int $id)
    {
        $user = $request->user();

        $attendance = Attendance::whereKey($id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($attendance->StampCorrectionRequests()->where('status', 'awaiting_approval')->exists()) {
            return back()->withErrors(['status' => '承認待ちのため修正はできません。'])->withInput();
        }

        $data = $request->validated();


        $workDate = Carbon::parse($attendance->work_date);

        $requestedClockIn  = $workDate->copy()->setTimeFromTimeString($data['clock_in_at']);
        $requestedClockOut = $workDate->copy()->setTimeFromTimeString($data['clock_out_at']);

        $requestedBreaks = collect($data['breaks'] ?? [])
            ->filter(fn($b) => !empty($b['break_in_at']) && !empty($b['break_out_at']))
            ->map(function ($b) use ($workDate) {
                return [
                    'break_in_at'  => $workDate->copy()->setTimeFromTimeString($b['break_in_at'])->toDateTimeString(),
                    'break_out_at' => $workDate->copy()->setTimeFromTimeString($b['break_out_at'])->toDateTimeString(),
                ];
            })
            ->values()
            ->all();

        DB::transaction(function () use ($attendance, $requestedClockIn, $requestedClockOut, $data, $requestedBreaks) {
            StampCorrectionRequest::create([
                'attendance_id'           => $attendance->id,
                'requested_clock_in_at'   => $requestedClockIn,
                'requested_clock_out_at'  => $requestedClockOut,
                'requested_note'          => $data['note'],
                'requested_breaks'        => empty($requestedBreaks) ? null : $requestedBreaks,
            ]);
        });

        return redirect()
            ->route('attendances.show', $attendance->id)
            ->with('flash_message', '修正申請を送信しました');
    }
}

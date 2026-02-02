<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


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
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\AdminAttendanceRequest;
use Illuminate\Validation\ValidationException;

class AdminAttendanceController extends Controller
{
    //
    public function index(Request $request)
    {
        $dateParam = (string) $request->query('date', now()->toDateString());

        try {
            $day = Carbon::createFromFormat('Y-m-d', $dateParam)->startOfDay();
        } catch (\Throwable $e) {
            $day = now()->startOfDay();
        }

        $users = User::query()
            ->orderBy('id')
            ->get();

        $attendances = Attendance::with(['breakTimes' => fn($q) => $q->orderBy('break_in_at')])
            ->whereDate('work_date', $day->toDateString())
            ->get()
            ->keyBy('user_id');

        $rows = $users->map(function (User $user) use ($attendances) {
            $a = $attendances->get($user->id);

            return [
                'name'      => $user->name,
                'id'        => $a?->id,
                'clock_in'  => $a?->clock_in_at?->format('H:i') ?? '',
                'clock_out' => $a?->clock_out_at?->format('H:i') ?? '',
                'break'     => ($a && $a->clock_out_at) ? $a->breakDurationLabel() : '',
                'work'      => ($a && $a->clock_out_at) ? $a->workDurationLabel() : '',
            ];
        });

        return view('admin.attendance.list', [
            'rows'      => $rows,
            'titleDate' => $day->format('Y年n月j日'),
            'dateLabel' => $day->format('Y/m/d'),
            'prevDate'  => $day->copy()->subDay()->toDateString(),
            'nextDate'  => $day->copy()->addDay()->toDateString(),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $attendance = Attendance::with([
            'user',
            'breakTimes' => fn($q) => $q->orderBy('break_in_at'),
        ])->findOrFail($id);

        $workDate = $attendance->work_date instanceof \Carbon\CarbonInterface
            ? $attendance->work_date
            : Carbon::parse($attendance->work_date);

        $pending = $attendance->stampCorrectionRequests()
            ->where('status', 'awaiting_approval')
            ->latest('id')
            ->first();

        $hasAwaitingApproval = (bool) $pending;

        $displayClockInAt  = $pending?->requested_clock_in_at  ?? $attendance->clock_in_at;
        $displayClockOutAt = $pending?->requested_clock_out_at ?? $attendance->clock_out_at;
        $displayNote       = $pending?->requested_note         ?? $attendance->note;

        if ($pending) {
            $breakRows = collect($pending->requested_breaks ?? [])
                ->map(fn($b) => (object) [
                    'break_in_at'  => Carbon::parse($b['break_in_at']),
                    'break_out_at' => Carbon::parse($b['break_out_at']),
                ])
                ->values();
        } else {
            $breakRows = $attendance->breakTimes
                ->map(fn($bt) => (object) [
                    'break_in_at'  => $bt->break_in_at,
                    'break_out_at' => $bt->break_out_at,
                ])
                ->values();
        }

        while ($breakRows->count() < 1) {
            $breakRows->push((object) ['break_in_at' => null, 'break_out_at' => null]);
        }

        $latestRequest = $attendance->stampCorrectionRequests()
            ->latest('id')
            ->first();

        $canApprove = $latestRequest && $latestRequest->status === 'awaiting_approval';
        $isApproved = $latestRequest && $latestRequest->status === 'approved';

        return view('admin.attendance.show', [
            'attendance' => $attendance,
            'user'       => $attendance->user,
            'yearLabel'  => $workDate->format('Y年'),
            'mdLabel'    => $workDate->format('n月j日'),

            'hasAwaitingApproval' => $hasAwaitingApproval,
            'pendingAwaitingApproval' => $pending,
            'latestRequest' => $latestRequest,
            'canApprove' => $canApprove,
            'isApproved' => $isApproved,

            'displayClockInAt'  => $displayClockInAt,
            'displayClockOutAt' => $displayClockOutAt,
            'displayNote'       => $displayNote,
            'breakRows'         => $breakRows,
        ]);
    }

    public function update(AdminAttendanceRequest $request, int $id)
    {
        $data = $request->validated();

        DB::transaction(function () use ($id, $data) {

            $attendance = Attendance::whereKey($id)->lockForUpdate()->firstOrFail();

            if ($attendance->stampCorrectionRequests()
                ->where('status', 'awaiting_approval')
                ->exists()
            ) {
                throw ValidationException::withMessages([
                    'status' => '承認待ちのため修正はできません。',
                ]);
            }

            $workDate = Carbon::parse($attendance->work_date)->startOfDay();

            $clockIn  = $workDate->copy()->setTimeFromTimeString($data['clock_in_at']);
            $clockOut = $workDate->copy()->setTimeFromTimeString($data['clock_out_at']);

            $breaks = collect($data['breaks'] ?? [])
                ->filter(fn($b) => filled($b['break_in_at'] ?? null) && filled($b['break_out_at'] ?? null))
                ->map(fn($b) => [
                    'break_in_at'  => $workDate->copy()->setTimeFromTimeString($b['break_in_at']),
                    'break_out_at' => $workDate->copy()->setTimeFromTimeString($b['break_out_at']),
                ])
                ->values()
                ->all();

            $attendance->update([
                'clock_in_at'  => $clockIn,
                'clock_out_at' => $clockOut,
                'note'         => $data['note'],
            ]);

            $attendance->breakTimes()->delete();
            if (!empty($breaks)) {
                $attendance->breakTimes()->createMany($breaks);
            }
        });

        return redirect()
            ->route('admin.attendances.show', $id)
            ->with('flash_message', '修正を反映しました');
    }

    public function approve(int $id)
    {
        DB::transaction(function () use ($id) {

            $scr = StampCorrectionRequest::whereKey($id)->lockForUpdate()->firstOrFail();

            if ($scr->status !== 'awaiting_approval') {
                throw ValidationException::withMessages(['status' => '承認待ちではありません。']);
            }

            $attendance = Attendance::whereKey($scr->attendance_id)->lockForUpdate()->firstOrFail();

            $workDate = Carbon::parse($attendance->work_date)->startOfDay();

            $attendance->update([
                'clock_in_at'  => $scr->requested_clock_in_at,
                'clock_out_at' => $scr->requested_clock_out_at,
                'note'         => $scr->requested_note,
            ]);

            $breaks = collect($scr->requested_breaks ?? [])
                ->map(fn($b) => [
                    'break_in_at'  => Carbon::parse($b['break_in_at']),
                    'break_out_at' => Carbon::parse($b['break_out_at']),
                ])
                ->values()
                ->all();

            $attendance->breakTimes()->delete();
            if (!empty($breaks)) {
                $attendance->breakTimes()->createMany($breaks);
            }

            $scr->update([
                'status'      => 'approved',
                'approved_at' => now(),
            ]);
        });

        return redirect()->back()->with('flash_message', '承認しました');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StampCorrectionRequest;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestApproveController extends Controller
{
    //
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'awaiting');
        $status = $tab === 'approved' ? 'approved' : 'awaiting_approval';

        $labels = [
            'awaiting_approval' => '承認待ち',
            'approved'          => '承認済み',
        ];

        $requests = StampCorrectionRequest::with(['attendance.user'])
            ->where('status', $status)
            ->orderByDesc('created_at')
            ->get();

        $rows = $requests->map(function ($r) use ($labels) {
            return [
                'id'             => $r->id, // 申請ID
                'status_label'   => $labels[$r->status] ?? $r->status,
                'name'           => $r->attendance->user->name,
                'target_date'    => optional($r->attendance->work_date)->format('Y/m/d'),
                'requested_note' => $r->requested_note,
                'applied_at'     => $r->created_at->format('Y/m/d'),
            ];
        });

        return view('admin.request.index', [
            'tab'  => $tab,
            'rows' => $rows,
        ]);
    }

    public function show(int $id)
    {
        $scr = StampCorrectionRequest::with(['attendance.user'])->findOrFail($id);
        $attendance = $scr->attendance;

        $workDate = $attendance->work_date instanceof \Carbon\CarbonInterface
            ? $attendance->work_date
            : Carbon::parse($attendance->work_date);

        $breakRows = collect($scr->requested_breaks ?? [])
            ->map(fn($b) => (object) [
                'break_in_at'  => Carbon::parse($b['break_in_at']),
                'break_out_at' => Carbon::parse($b['break_out_at']),
            ])
            ->values();

        while ($breakRows->count() < 2) {
            $breakRows->push((object) ['break_in_at' => null, 'break_out_at' => null]);
        }

        return view('admin.request.show', [
            'scr'        => $scr,
            'attendance' => $attendance,
            'user'       => $attendance->user,
            'yearLabel'  => $workDate->format('Y年'),
            'mdLabel'    => $workDate->format('n月j日'),

            'breakRows'         => $breakRows,
            'displayClockInAt'  => $scr->requested_clock_in_at,
            'displayClockOutAt' => $scr->requested_clock_out_at,
            'displayNote'       => $scr->requested_note,
            'canApprove'        => $scr->status === 'awaiting_approval',
            'isApproved'        => $scr->status === 'approved',
        ]);
    }

    public function approve(int $id)
    {
        DB::transaction(function () use ($id) {
            $scr = StampCorrectionRequest::whereKey($id)->lockForUpdate()->firstOrFail();

            if ($scr->status !== 'awaiting_approval') {
                throw ValidationException::withMessages(['status' => '承認待ちではありません。']);
            }

            $attendance = Attendance::whereKey($scr->attendance_id)->lockForUpdate()->firstOrFail();

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
                'approved_by' => auth('admin')->id(),
                'approved_at' => now(),
            ]);
        });

        return redirect()
            ->route('admin.requests.show', $id)
            ->with('flash_message', '承認しました');
    }
}

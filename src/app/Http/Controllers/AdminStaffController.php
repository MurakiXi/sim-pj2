<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminStaffController extends Controller
{
    //
    public function index()
    {
        $users = User::query()
            ->select(['id', 'name', 'email'])
            ->orderBy('id')
            ->paginate(20);

        return view('admin.staff', compact('users'));
    }

    public function attendanceIndex(Request $request, User $user)
    {
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

        return view('admin.attendance.index', [
            'name'       => $user->name,
            'userId'     => $user->id,
            'rows'       => $rows,
            'monthLabel' => $current->format('Y/m'),
            'prevMonth'  => $current->copy()->subMonth()->format('Y-m'),
            'nextMonth'  => $current->copy()->addMonth()->format('Y-m'),
            'currentMonth' => $current->format('Y-m'),
        ]);
    }

    public function attendanceCsv(Request $request, User $user)
    {
        $monthParam = (string) $request->query('month', now()->format('Y-m'));

        try {
            $current = Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth();
        } catch (\Throwable $e) {
            $current = now()->startOfMonth();
            $monthParam = $current->format('Y-m');
        }

        $start = $current->copy()->startOfMonth();
        $end   = $current->copy()->endOfMonth();

        $attendances = Attendance::with(['breakTimes' => fn($q) => $q->orderBy('break_in_at')])
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $attendanceByDate = $attendances->keyBy(fn($a) => $a->work_date->toDateString());

        $filename = "attendances_{$user->id}_{$current->format('Ym')}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=SJIS-win',
        ];

        return response()->streamDownload(function () use ($start, $end, $attendanceByDate) {
            $out = fopen('php://output', 'w');

            $enc = fn($v) => mb_convert_encoding((string) $v, 'SJIS-win', 'UTF-8');

            fputcsv($out, array_map($enc, ['日付', '出勤', '退勤', '休憩', '合計']));

            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $a = $attendanceByDate->get($d->toDateString());

                $dateLabel = $d->format('m/d') . '（' . ['日', '月', '火', '水', '木', '金', '土'][$d->dayOfWeek] . '）';

                $row = [
                    $dateLabel,
                    $a?->clock_in_at?->format('H:i') ?? '',
                    $a?->clock_out_at?->format('H:i') ?? '',
                    ($a && $a->clock_out_at) ? $a->breakDurationLabel() : '',
                    ($a && $a->clock_out_at) ? $a->workDurationLabel() : '',
                ];

                fputcsv($out, array_map($enc, $row));
            }

            fclose($out);
        }, $filename, $headers);
    }
}

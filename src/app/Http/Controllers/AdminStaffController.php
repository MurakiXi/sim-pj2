<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

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

        try {
            $month = Carbon::createFromFormat('Y-m-d', $monthParam)->startOfDay();
        } catch (\Throwable $e) {
            $month = now()->startOfDay();
        }


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
        ]);
    }
}

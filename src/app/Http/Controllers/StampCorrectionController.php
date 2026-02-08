<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class StampCorrectionController extends Controller
{
    //
    public function index(Request $request)
    {
        $dateParam = (string) $request->query('date', now()->toDateString());
        $day = Carbon::parse($dateParam)->startOfDay();

        $users = User::query()
            ->orderBy('id')
            ->get();

        $attendances = Attendance::with(['breakTimes' => fn($q) => $q->orderBy('break_in_at')])
            ->whereDate('work_date', $day->toDateString())
            ->get()
            ->keyBy('user_id');

        $rows = $users->map(function (User $u) use ($attendances) {
            $a = $attendances->get($u->id);

            return [
                'user_name' => $u->name,
                'id'        => $a?->id,
                'clock_in'  => $a?->clock_in_at?->format('H:i') ?? '',
                'clock_out' => $a?->clock_out_at?->format('H:i') ?? '',
                'break'     => ($a && $a->clock_out_at) ? $a->breakDurationLabel() : '',
                'work'      => ($a && $a->clock_out_at) ? $a->workDurationLabel() : '',
            ];
        });

        return view('admin.attendance.index', [
            'rows'      => $rows,
            'dateLabel' => $day->format('Y/m/d'),
            'prevDate'  => $day->copy()->subDay()->toDateString(),
            'nextDate'  => $day->copy()->addDay()->toDateString(),
        ]);
    }
}

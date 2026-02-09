<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StampCorrectionRequest;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StampCorrectionController extends Controller
{
    //
    public function index(Request $request)
    {
        $status = (string) $request->query('status', 'awaiting_approval');
        $allowed = ['awaiting_approval', 'approved'];
        if (!in_array($status, $allowed, true)) {
            $status = 'awaiting_approval';
        }

        $q = StampCorrectionRequest::query()
            ->with(['attendance.user', 'admin'])
            ->where('status', $status)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (!auth('admin')->check()) {
            $user = $request->user();
            $q->whereHas('attendance', fn($a) => $a->where('user_id', $user->id));
        }

        $requests = $q->paginate(20)->withQueryString();

        return auth('admin')->check()
            ? view('admin.request.index', compact('requests', 'status'))
            : view('stamp_correction_request.index', compact('requests', 'status'));
    }


    public function approve(Request $request, int $id)
    {
        DB::transaction(function () use ($id) {

            $scr = StampCorrectionRequest::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($scr->status !== 'awaiting_approval') {
                abort(409);
            }

            $attendance = Attendance::whereKey($scr->attendance_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!is_null($scr->requested_clock_in_at)) {
                $attendance->clock_in_at = $scr->requested_clock_in_at;
            }
            if (!is_null($scr->requested_clock_out_at)) {
                $attendance->clock_out_at = $scr->requested_clock_out_at;
            }

            $attendance->note = $scr->requested_note ?? '';

            $attendance->save();

            if (!is_null($scr->requested_breaks)) {

                $attendance->breakTimes()->delete();

                foreach ($scr->requested_breaks as $b) {

                    $in  = $b['break_in_at']  ?? null;
                    $out = $b['break_out_at'] ?? null;

                    if (is_null($in) || is_null($out)) {
                        continue;
                    }

                    if (Carbon::parse($in)->gt(Carbon::parse($out))) {
                        continue; // もしくは abort(422)
                    }

                    $attendance->breakTimes()->create([
                        'break_in_at'  => $in,
                        'break_out_at' => $out,
                    ]);
                }
            }

            $scr->status      = 'approved';
            $scr->approved_by = auth('admin')->id();
            $scr->approved_at = now();
            $scr->save();
        });

        return redirect()->route('stamp_correction_request.list', ['status' => 'awaiting_approval']);
    }
}

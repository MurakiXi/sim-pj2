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
            : view('attendance.request', compact('requests', 'status'));
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StampCorrectionRequest;

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
}

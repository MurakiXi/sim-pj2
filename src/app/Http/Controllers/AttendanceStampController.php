<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AttendanceStampController extends Controller
{
    //
    public function create()
    {
        return view('attendance.create');
    }
}

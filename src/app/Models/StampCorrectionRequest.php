<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StampCorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'requested_clock_in_at',
        'requested_clock_out_at',
        'requested_note',
        'requested_breaks',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'requested_clock_in_at'  => 'datetime',
        'requested_clock_out_at' => 'datetime',
        'requested_breaks'       => 'array',
        'approved_at'            => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }
}

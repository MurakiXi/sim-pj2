<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    use HasFactory;

    protected $fillable = ['attendance_id', 'break_in_at', 'break_out_at'];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'work_date', 'clock_in_at', 'clock_out_at', 'note'];

    protected $casts = [
        'work_date'    => 'date',
        'clock_in_at'  => 'datetime',
        'clock_out_at' => 'datetime',
    ];

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function breakSeconds(): int
    {
        return $this->breakTimes
            ->filter(fn(BreakTime $b) => !is_null($b->break_out_at))
            ->sum(fn(BreakTime $b) => $b->break_in_at->diffInSeconds($b->break_out_at));
    }

    public function workSeconds(): ?int
    {
        if (is_null($this->clock_in_at) || is_null($this->clock_out_at)) {
            return null;
        }
        return $this->clock_in_at->diffInSeconds($this->clock_out_at) - $this->breakSeconds();
    }

    public function breakDurationLabel(): string
    {
        if (is_null($this->clock_out_at)) return '';
        return self::formatSecondsToHm($this->breakSeconds());
    }

    public function workDurationLabel(): string
    {
        $sec = $this->workSeconds();
        if ($sec === null) return '';
        return self::formatSecondsToHm($sec);
    }

    private static function formatSecondsToHm(int $seconds): string
    {
        if ($seconds < 0) return '';
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        return sprintf('%d:%02d', $h, $m);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stampCorrectionRequests()
    {
        return $this->hasMany(StampCorrectionRequest::class);
    }
}

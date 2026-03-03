<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use Carbon\CarbonImmutable;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $emails = [
            'user1@example.com',
            'user2@example.com',
            'user3@example.com',
        ];

        $users = User::whereIn('email', $emails)->get()->keyBy('email');

        $days = collect([0, 1, 2])->map(function ($subDays) {
            return CarbonImmutable::today('Asia/Tokyo')->subDays($subDays);
        });

        foreach ($emails as $email) {
            $user = $users->get($email);
            if (!$user) continue;

            foreach ($days as $day) {
                $attendance = Attendance::updateOrCreate(
                    [
                        'user_id'   => $user->id,
                        'work_date' => $day->toDateString(),
                    ],
                    [
                        'clock_in_at'  => $day->setTime(9, 0),
                        'clock_out_at' => $day->setTime(18, 0),
                        'note'         => 'Seeder投入データ',
                    ]
                );

                $attendance->breakTimes()->delete();
                $attendance->breakTimes()->createMany([
                    [
                        'break_in_at'  => $day->setTime(12, 0),
                        'break_out_at' => $day->setTime(13, 0),
                    ],
                ]);
            }
        }
    }
}

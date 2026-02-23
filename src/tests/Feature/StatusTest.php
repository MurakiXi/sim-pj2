<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;

class StatusTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'name' => 'テスト太郎',
            'email' => 'taro@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ], $overrides));
    }

    public function test_status_off(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertOk();
        $response->assertSeeText('勤務外');

        Carbon::setTestNow();
    }

    public function test_status_working(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->createUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in_at' => now()->copy()->setTime(9, 0),
            'clock_out_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertOk();
        $response->assertSeeText('出勤中');

        Carbon::setTestNow();
    }

    public function test_status_break(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 10, 0, 0, 'Asia/Tokyo'));

        $user = $this->createUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in_at' => now()->copy()->setTime(9, 0),
            'clock_out_at' => null,
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_in_at' => now()->copy()->setTime(10, 0),
            'break_out_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertOk();
        $response->assertSeeText('休憩中');

        Carbon::setTestNow();
    }

    public function test_status_done(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 18, 0, 0, 'Asia/Tokyo'));

        $user = $this->createUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in_at' => now()->copy()->setTime(9, 0),
            'clock_out_at' => now()->copy()->setTime(18, 0),
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertOk();
        $response->assertSeeText('退勤済');

        Carbon::setTestNow();
    }
}

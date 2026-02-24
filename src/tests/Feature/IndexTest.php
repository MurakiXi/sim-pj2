<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Attendance;

class IndexTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    private function dateLabel(string $ymd): string
    {
        $d = Carbon::createFromFormat('Y-m-d', $ymd, 'Asia/Tokyo');
        $youbi = ['日', '月', '火', '水', '木', '金', '土'][$d->dayOfWeek];

        return $d->format('m/d') . "({$youbi})";
    }

    public function test_attendance_list_shows_all_records_of_logged_in_user(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 15, 12, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $other = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $a1 = Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => '2026-02-03',
            'clock_in_at'  => Carbon::create(2026, 2, 3, 9, 11, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 3, 18, 22, 0, 'Asia/Tokyo'),
        ]);

        $a2 = Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => '2026-02-19',
            'clock_in_at'  => Carbon::create(2026, 2, 19, 10, 33, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 19, 19, 44, 0, 'Asia/Tokyo'),
        ]);

        $a3 = Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => '2026-02-28',
            'clock_in_at'  => Carbon::create(2026, 2, 28, 7, 55, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 28, 16, 6, 0, 'Asia/Tokyo'),
        ]);

        Attendance::create([
            'user_id'      => $other->id,
            'work_date'    => '2026-02-05',
            'clock_in_at'  => Carbon::create(2026, 2, 5, 6, 6, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 5, 15, 15, 0, 'Asia/Tokyo'),
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendances.index', ['month' => '2026-02']))
            ->assertOk();

        $html = $response->getContent();

        foreach ([$a1, $a2, $a3] as $a) {
            $workDate = $a->work_date?->toDateString();
            $this->assertNotNull($workDate);

            $dateLabel = $this->dateLabel($workDate);
            $clockIn   = $a->clock_in_at->format('H:i');
            $clockOut  = $a->clock_out_at->format('H:i');
            $detailUrl = route('attendances.show', $a->id);

            $pattern = '/<tr class="index__table-row">[\s\S]*?'
                . preg_quote($dateLabel, '/')
                . '[\s\S]*?'
                . preg_quote($clockIn, '/')
                . '[\s\S]*?'
                . preg_quote($clockOut, '/')
                . '[\s\S]*?'
                . preg_quote($detailUrl, '/')
                . '[\s\S]*?<\/tr>/us';

            $this->assertMatchesRegularExpression($pattern, $html);
        }

        $this->assertStringNotContainsString('06:06', $html);
        $this->assertStringNotContainsString('15:15', $html);

        Carbon::setTestNow();
    }

    public function test_current_month_is_shown_on_attendance_list(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendances.index'))
            ->assertOk();

        $expectedMonthLabel = now()->format('Y/m');

        $response->assertSeeText($expectedMonthLabel);

        Carbon::setTestNow();
    }

    public function test_prev_month_link_shows_previous_month_records(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['email_verified_at' => now()]);

        $jan = Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => '2026-01-19',
            'clock_in_at'  => Carbon::create(2026, 1, 19, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 1, 19, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        $feb = Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => '2026-02-10',
            'clock_in_at'  => Carbon::create(2026, 2, 10, 10, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 10, 19, 0, 0, 'Asia/Tokyo'),
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendances.index'))
            ->assertOk();

        $prevMonth = now()->copy()->subMonth()->format('Y-m');
        $prevHref  = route('attendances.index', ['month' => $prevMonth]);

        $response->assertSee('href="' . $prevHref . '"', false);

        $prev = $this->actingAs($user)
            ->get($prevHref)
            ->assertOk();

        $html = $prev->getContent();

        $this->assertStringContainsString($jan->clock_in_at->format('H:i'), $html);
        $this->assertStringContainsString($jan->clock_out_at->format('H:i'), $html);

        $this->assertStringNotContainsString($feb->clock_in_at->format('H:i'), $html);
        $this->assertStringNotContainsString($feb->clock_out_at->format('H:i'), $html);

        Carbon::setTestNow();
    }

    public function test_next_month_link_shows_next_month_records(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['email_verified_at' => now()]);

        $mar = Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => '2026-03-19',
            'clock_in_at'  => Carbon::create(2026, 3, 19, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 19, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        $feb = Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => '2026-02-10',
            'clock_in_at'  => Carbon::create(2026, 2, 10, 10, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 10, 19, 0, 0, 'Asia/Tokyo'),
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendances.index'))
            ->assertOk();

        $nextMonth = now()->copy()->addMonth()->format('Y-m');
        $nextHref  = route('attendances.index', ['month' => $nextMonth]);

        $response->assertSee('href="' . $nextHref . '"', false);

        $next = $this->actingAs($user)
            ->get($nextHref)
            ->assertOk();

        $html = $next->getContent();

        $this->assertStringContainsString($mar->clock_in_at->format('H:i'), $html);
        $this->assertStringContainsString($mar->clock_out_at->format('H:i'), $html);

        $this->assertStringNotContainsString($feb->clock_in_at->format('H:i'), $html);
        $this->assertStringNotContainsString($feb->clock_out_at->format('H:i'), $html);

        Carbon::setTestNow();
    }

    public function test_click_detail_navigates_to_that_days_attendance_detail(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['email_verified_at' => now()]);

        $attendance = Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => '2026-02-19',
            'clock_in_at'  => Carbon::create(2026, 2, 19, 9, 7, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 19, 18, 3, 0, 'Asia/Tokyo'),
        ]);

        $index = $this->actingAs($user)
            ->get(route('attendances.index', ['month' => '2026-02']))
            ->assertOk();

        $indexHtml = $index->getContent();

        $detailUrl = route('attendances.show', $attendance->id);

        $dateLabel = $this->dateLabel($attendance->work_date->toDateString());

        $pattern = '/<tr class="index__table-row">[\s\S]*?'
            . preg_quote($dateLabel, '/')
            . '[\s\S]*?'
            . preg_quote($detailUrl, '/')
            . '[\s\S]*?<\/tr>/us';

        $this->assertMatchesRegularExpression($pattern, $indexHtml);

        $detail = $this->actingAs($user)
            ->get($detailUrl)
            ->assertOk();

        $detail = $this->actingAs($user)->get($detailUrl)->assertOk();

        $detail->assertSeeText($attendance->work_date->format('Y年'));
        $detail->assertSeeText($attendance->work_date->format('n月j日'));

        $detailHtml = $detail->getContent();
        $this->assertMatchesRegularExpression(
            '/name="clock_in_at"[^>]*value="' . preg_quote($attendance->clock_in_at->format('H:i'), '/') . '"/u',
            $detailHtml
        );
        $this->assertMatchesRegularExpression(
            '/name="clock_out_at"[^>]*value="' . preg_quote($attendance->clock_out_at->format('H:i'), '/') . '"/u',
            $detailHtml
        );

        Carbon::setTestNow();
    }

    private function createAttendance(User $user, array $overrides = []): Attendance
    {
        return Attendance::create(array_merge([
            'user_id'      => $user->id,
            'work_date'    => '2026-02-19',
            'clock_in_at'  => Carbon::create(2026, 2, 19, 9, 7, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 19, 18, 3, 0, 'Asia/Tokyo'),
            'note'         => 'テスト備考',
        ], $overrides));
    }


    public function test_request_list_detail_navigates_to_attendance_detail(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['email_verified_at' => now()]);

        $attendance = $this->createAttendance($user, [
            'work_date'    => '2026-02-19',
            'clock_in_at'  => Carbon::create(2026, 2, 19, 9, 7, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 19, 18, 3, 0, 'Asia/Tokyo'),
            'note'         => '元備考',
        ]);

        $this->actingAs($user)->patch(route('attendances.update', $attendance->id), [
            'clock_in_at'  => '10:00',
            'clock_out_at' => '19:00',
            'note'         => '申請備考',
            'breaks'       => [],
        ])->assertStatus(302);

        $list = $this->actingAs($user)
            ->get(route('stamp_correction_requests.index', ['status' => 'awaiting_approval']))
            ->assertOk();

        $html = $list->getContent();

        $detailUrl = route('attendances.show', $attendance->id);
        $this->assertStringContainsString($detailUrl, $html);

        $detail = $this->actingAs($user)->get($detailUrl)->assertOk();
        $detail->assertSeeText($attendance->work_date->format('Y年'));
        $detail->assertSeeText($attendance->work_date->format('n月j日'));

        Carbon::setTestNow();
    }
}

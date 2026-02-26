<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class StaffTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    private function createAttendance(User $user, array $overrides = []): Attendance
    {
        $workDate = $overrides['work_date'] ?? now()->toDateString();
        $base = Carbon::parse($workDate, 'Asia/Tokyo')->startOfDay();

        return Attendance::create(array_merge([
            'user_id'      => $user->id,
            'work_date'    => $workDate,
            'clock_in_at'  => $base->copy()->setTime(9, 7),
            'clock_out_at' => $base->copy()->setTime(18, 3),
            'note'         => 'テスト備考',
        ], $overrides));
    }

    private function dateLabel(string $ymd): string
    {
        $d = Carbon::createFromFormat('Y-m-d', $ymd, 'Asia/Tokyo');
        $youbi = ['日', '月', '火', '水', '木', '金', '土'][$d->dayOfWeek];

        return $d->format('m/d') . "({$youbi})";
    }


    public function test_admin_can_view_all_users_name_and_email_on_staff_list(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $u1 = User::factory()->create([
            'name'  => 'テスト 太郎',
            'email' => 'example@test.co.jp',
            'email_verified_at' => now(),
        ]);

        $u2 = User::factory()->create([
            'name'  => '試験 次郎',
            'email' => 'example2@test.co.jp',
            'email_verified_at' => now(),
        ]);

        $admin = Admin::create([
            'name'     => '管理者',
            'email'    => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $res = $this->actingAs($admin, 'admin')
            ->get(route('admin.staff.index'))
            ->assertOk();

        $html = $res->getContent();

        $this->assertMatchesRegularExpression(
            '/<tr class="index__table-row">[\s\S]*?'
                . preg_quote($u1->name, '/')
                . '[\s\S]*?'
                . preg_quote($u1->email, '/')
                . '[\s\S]*?<\/tr>/us',
            $html
        );

        $this->assertMatchesRegularExpression(
            '/<tr class="index__table-row">[\s\S]*?'
                . preg_quote($u2->name, '/')
                . '[\s\S]*?'
                . preg_quote($u2->email, '/')
                . '[\s\S]*?<\/tr>/us',
            $html
        );

        $detail1 = route('admin.staff.attendances.index', ['user' => $u1->id]);

        $this->assertMatchesRegularExpression(
            '/<tr class="index__table-row">[\s\S]*?'
                . preg_quote($u1->name, '/')
                . '[\s\S]*?'
                . preg_quote($u1->email, '/')
                . '[\s\S]*?href="' . preg_quote($detail1, '/') . '"'
                . '[\s\S]*?<\/tr>/us',
            $html
        );

        Carbon::setTestNow();
    }

    public function test_admin_can_view_selected_users_attendance_list(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $u1 = User::factory()->create([
            'name'  => 'テスト 太郎',
            'email' => 'example@test.co.jp',
            'email_verified_at' => now(),
        ]);

        $u2 = User::factory()->create([
            'name'  => '試験 次郎',
            'email' => 'example2@test.co.jp',
            'email_verified_at' => now(),
        ]);

        $a1 = $this->createAttendance($u1, [
            'work_date'    => '2026-02-19',
            'clock_in_at'  => Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 19, 18, 0, 0, 'Asia/Tokyo'),
            'note'         => '元備考1',
        ]);

        $a2 = $this->createAttendance($u1, [
            'work_date'    => '2026-02-20',
            'clock_in_at'  => Carbon::create(2026, 2, 20, 10, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 20, 19, 0, 0, 'Asia/Tokyo'),
            'note'         => '元備考2',
        ]);

        $this->createAttendance($u2, [
            'work_date'    => '2026-02-19',
            'clock_in_at'  => Carbon::create(2026, 2, 19, 7, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 19, 16, 0, 0, 'Asia/Tokyo'),
            'note'         => 'u2備考',
        ]);

        $admin = Admin::create([
            'name'     => '管理者',
            'email'    => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $res = $this->actingAs($admin, 'admin')
            ->get(route('admin.staff.attendances.index', ['user' => $u1->id, 'date' => '2026-02']))
            ->assertOk();

        $html = $res->getContent();

        $this->assertMatchesRegularExpression(
            '/<tr[^>]*>[\s\S]*?'
                . preg_quote('09:00', '/')
                . '[\s\S]*?'
                . preg_quote('18:00', '/')
                . '[\s\S]*?<\/tr>/us',
            $html
        );

        $this->assertMatchesRegularExpression(
            '/<tr[^>]*>[\s\S]*?'
                . preg_quote('10:00', '/')
                . '[\s\S]*?'
                . preg_quote('19:00', '/')
                . '[\s\S]*?<\/tr>/us',
            $html
        );

        $res->assertDontSeeText('07:00');
        $res->assertDontSeeText('16:00');

        Carbon::setTestNow();
    }

    public function test_prev_month_link_shows_previous_month_records(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $admin = Admin::create([
            'name'     => '管理者',
            'email'    => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $u1 = User::factory()->create([
            'name'  => 'テスト 太郎',
            'email' => 'example@test.co.jp',
            'email_verified_at' => now(),
        ]);

        $jan = Attendance::create([
            'user_id'      => $u1->id,
            'work_date'    => '2026-01-19',
            'clock_in_at'  => Carbon::create(2026, 1, 19, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 1, 19, 18, 0, 0, 'Asia/Tokyo'),
            'note'         => '1月',
        ]);

        $feb = Attendance::create([
            'user_id'      => $u1->id,
            'work_date'    => '2026-02-10',
            'clock_in_at'  => Carbon::create(2026, 2, 10, 10, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 10, 19, 0, 0, 'Asia/Tokyo'),
            'note'         => '2月',
        ]);

        $currentMonth = now()->format('Y-m');
        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.staff.attendances.index', ['user' => $u1->id, 'month' => $currentMonth]))
            ->assertOk();

        $prevMonth = now()->copy()->subMonth()->format('Y-m');
        $prevHref  = route('admin.staff.attendances.index', ['user' => $u1->id, 'month' => $prevMonth]);

        $response->assertSee('href="' . e($prevHref) . '"', false);

        $prev = $this->actingAs($admin, 'admin')
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

        $admin = Admin::create([
            'name'     => '管理者',
            'email'    => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $u1 = User::factory()->create([
            'name'  => 'テスト 太郎',
            'email' => 'example@test.co.jp',
            'email_verified_at' => now(),
        ]);

        $mar = Attendance::create([
            'user_id'      => $u1->id,
            'work_date'    => '2026-03-19',
            'clock_in_at'  => Carbon::create(2026, 3, 19, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 19, 18, 0, 0, 'Asia/Tokyo'),
            'note'         => '3月',
        ]);

        $feb = Attendance::create([
            'user_id'      => $u1->id,
            'work_date'    => '2026-02-10',
            'clock_in_at'  => Carbon::create(2026, 2, 10, 10, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 10, 19, 0, 0, 'Asia/Tokyo'),
            'note'         => '2月',
        ]);

        $currentMonth = now()->format('Y-m');
        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.staff.attendances.index', ['user' => $u1->id, 'month' => $currentMonth]))
            ->assertOk();

        $nextMonth = now()->copy()->addMonth()->format('Y-m');
        $nextHref  = route('admin.staff.attendances.index', ['user' => $u1->id, 'month' => $nextMonth]);

        $response->assertSee('href="' . e($nextHref) . '"', false);

        $next = $this->actingAs($admin, 'admin')
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

        $admin = Admin::create([
            'name'     => '管理者',
            'email'    => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'name' => 'テスト 太郎',
        ]);

        $attendance = Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => '2026-02-19',
            'clock_in_at'  => Carbon::create(2026, 2, 19, 9, 7, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 19, 18, 3, 0, 'Asia/Tokyo'),
            'note'         => 'テスト備考',
        ]);

        $index = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendances.index', ['date' => '2026-02-19']))
            ->assertOk();

        $indexHtml = $index->getContent();

        $detailUrl = route('admin.attendances.show', $attendance->id);

        $this->assertMatchesRegularExpression(
            '/<tr class="index__table-row">[\s\S]*?'
                . preg_quote($user->name, '/')
                . '[\s\S]*?href="' . preg_quote($detailUrl, '/') . '"'
                . '[\s\S]*?<\/tr>/us',
            $indexHtml
        );

        $detail = $this->actingAs($admin, 'admin')
            ->get($detailUrl)
            ->assertOk();

        $detail->assertSeeText($attendance->work_date->format('Y年'));
        $detail->assertSeeText($attendance->work_date->format('n月j日'));
        $detail->assertSeeText($user->name);

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
}

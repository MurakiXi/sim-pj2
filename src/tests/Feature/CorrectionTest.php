<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\Admin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Models\StampCorrectionRequest;

class CorrectionTest extends TestCase
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


    public function test_admin_attendance_detail_shows_selected_attendance_data(): void
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
            'clock_in_at'  => Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 19, 20, 0, 0, 'Asia/Tokyo'),
            'note'         => '備考テスト',
        ]);

        $attendance->breakTimes()->create([
            'break_in_at'  => Carbon::create(2026, 2, 19, 12, 0, 0, 'Asia/Tokyo'),
            'break_out_at' => Carbon::create(2026, 2, 19, 13, 0, 0, 'Asia/Tokyo'),
        ]);

        $res = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendances.show', $attendance->id))
            ->assertOk();

        $res->assertSeeText('勤怠詳細');
        $res->assertSeeText($user->name);
        $res->assertSeeText($attendance->work_date->format('Y年'));
        $res->assertSeeText($attendance->work_date->format('n月j日'));

        $html = $res->getContent();

        $this->assertMatchesRegularExpression(
            '/name="clock_in_at"[^>]*value="' . preg_quote($attendance->clock_in_at->format('H:i'), '/') . '"/u',
            $html
        );

        $this->assertMatchesRegularExpression(
            '/name="clock_out_at"[^>]*value="' . preg_quote($attendance->clock_out_at->format('H:i'), '/') . '"/u',
            $html
        );

        $this->assertMatchesRegularExpression(
            '/name="breaks\[0\]\[break_in_at\]"[^>]*value="12:00"/u',
            $html
        );

        $this->assertMatchesRegularExpression(
            '/name="breaks\[0\]\[break_out_at\]"[^>]*value="13:00"/u',
            $html
        );

        $this->assertMatchesRegularExpression(
            '/<textarea[^>]*name="note"[^>]*>\s*' . preg_quote($attendance->note, '/') . '\s*<\/textarea>/us',
            $html
        );


        $res->assertSeeText('修正');

        Carbon::setTestNow();
    }

    public function test_admin_update_shows_error_when_clock_in_is_after_clock_out(): void
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

        $attendance = $this->createAttendance($user, [
            'work_date' => now()->toDateString(),
        ]);

        $showUrl = route('admin.attendances.show', $attendance->id);

        $res = $this->actingAs($admin, 'admin')
            ->from($showUrl)
            ->patch(route('admin.attendances.update', $attendance->id), [
                'clock_in_at'  => '19:00',
                'clock_out_at' => '18:00',
                'note'         => 'テスト備考',
                'breaks'        => [],
            ]);

        $res->assertRedirect($showUrl);

        $res->assertSessionHasErrors([
            'clock_in_at' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);

        $this->followingRedirects()
            ->actingAs($admin, 'admin')
            ->patch(route('admin.attendances.update', $attendance->id), [
                'clock_in_at'  => '19:00',
                'clock_out_at' => '18:00',
                'note'         => 'テスト備考',
                'breaks'        => [],
            ])
            ->assertSeeText('出勤時間もしくは退勤時間が不適切な値です');

        Carbon::setTestNow();
    }

    public function test_admin_update_shows_error_when_break_in_is_after_clock_out(): void
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

        $attendance = $this->createAttendance($user, [
            'work_date' => now()->toDateString(),
        ]);

        $showUrl = route('admin.attendances.show', $attendance->id);

        $payload = [
            'clock_in_at'  => '09:07',
            'clock_out_at' => '18:00',
            'note'         => 'テスト備考',
            'breaks'       => [
                [
                    'break_in_at'  => '19:00',
                    'break_out_at' => '19:30',
                ],
            ],
        ];

        $res = $this->actingAs($admin, 'admin')
            ->from($showUrl)
            ->patch(route('admin.attendances.update', $attendance->id), $payload);

        $res->assertRedirect($showUrl);

        $this->followingRedirects()
            ->actingAs($admin, 'admin')
            ->patch(route('admin.attendances.update', $attendance->id), $payload)
            ->assertSeeText('休憩時間が不適切な値です');

        Carbon::setTestNow();
    }

    public function test_admin_update_shows_error_when_break_out_is_after_clock_out(): void
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

        $attendance = $this->createAttendance($user, [
            'work_date' => now()->toDateString(),
        ]);

        $showUrl = route('admin.attendances.show', $attendance->id);

        $payload = [
            'clock_in_at'  => '09:07',
            'clock_out_at' => '18:00',
            'note'         => 'テスト備考',
            'breaks'       => [
                [
                    'break_in_at'  => '17:00',
                    'break_out_at' => '18:30',
                ],
            ],
        ];

        $res = $this->actingAs($admin, 'admin')
            ->from($showUrl)
            ->patch(route('admin.attendances.update', $attendance->id), $payload);

        $res->assertRedirect($showUrl);

        $this->followingRedirects()
            ->actingAs($admin, 'admin')
            ->patch(route('admin.attendances.update', $attendance->id), $payload)
            ->assertSeeText('休憩時間もしくは退勤時間が不適切な値です');

        Carbon::setTestNow();
    }

    public function test_note_is_required_shows_validation_message(): void
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

        $attendance = $this->createAttendance($user);
        $showUrl = route('admin.attendances.show', $attendance->id);

        $res = $this->actingAs($admin, 'admin')
            ->from($showUrl)
            ->patch(route('admin.attendances.update', $attendance->id), [
                'clock_in_at'  => '09:07',
                'clock_out_at' => '18:03',
                'note'         => '',
                'breaks'       => [],
            ]);

        $res->assertRedirect($showUrl);

        $res->assertSessionHasErrors([
            'note' => '備考を記入してください',
        ]);

        $this->followingRedirects()
            ->actingAs($admin, 'admin')
            ->patch(route('admin.attendances.update', $attendance->id), [
                'clock_in_at'  => '09:07',
                'clock_out_at' => '18:03',
                'note'         => '',
                'breaks'       => [],
            ])
            ->assertSeeText('備考を記入してください');

        Carbon::setTestNow();
    }

    public function test_all_awaiting_requests_are_visible_in_request_list(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $u1 = User::factory()->create([
            'name' => 'テスト 太郎',
            'email_verified_at' => now(),
        ]);
        $u2 = User::factory()->create([
            'name' => '試験 次郎',
            'email_verified_at' => now(),
        ]);

        $a1 = $this->createAttendance($u1, [
            'work_date'    => '2026-02-19',
            'clock_in_at'  => Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 19, 18, 0, 0, 'Asia/Tokyo'),
            'note'         => '元備考1',
        ]);

        $a2 = $this->createAttendance($u2, [
            'work_date'    => '2026-02-19',
            'clock_in_at'  => Carbon::create(2026, 2, 19, 10, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 19, 19, 0, 0, 'Asia/Tokyo'),
            'note'         => '元備考2',
        ]);

        $r1 = StampCorrectionRequest::create([
            'attendance_id' => $a1->id,
            'requested_clock_in_at'  => Carbon::create(2026, 2, 19, 9, 5, 0, 'Asia/Tokyo'),
            'requested_clock_out_at' => Carbon::create(2026, 2, 19, 18, 5, 0, 'Asia/Tokyo'),
            'requested_note' => '【待ち】u1申請',
            'requested_breaks' => [],
        ]);
        $r1->forceFill(['status' => 'awaiting_approval'])->save();

        $r2 = StampCorrectionRequest::create([
            'attendance_id' => $a2->id,
            'requested_clock_in_at'  => Carbon::create(2026, 2, 19, 10, 5, 0, 'Asia/Tokyo'),
            'requested_clock_out_at' => Carbon::create(2026, 2, 19, 19, 5, 0, 'Asia/Tokyo'),
            'requested_note' => '【待ち】u2申請',
            'requested_breaks' => [],
        ]);
        $r2->forceFill(['status' => 'awaiting_approval'])->save();

        $admin = Admin::create([
            'name'     => '管理者',
            'email'    => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $r3 = StampCorrectionRequest::create([
            'attendance_id' => $a1->id,
            'requested_clock_in_at'  => Carbon::create(2026, 2, 19, 8, 55, 0, 'Asia/Tokyo'),
            'requested_clock_out_at' => Carbon::create(2026, 2, 19, 17, 55, 0, 'Asia/Tokyo'),
            'requested_note' => '【済み】混ぜ物',
            'requested_breaks' => [],
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);
        $r3->forceFill(['status' => 'approved'])->save();

        $this->actingAs($admin, 'admin')
            ->get(route('stamp_correction_requests.index', ['status' => 'awaiting_approval']))
            ->assertOk()
            ->assertSeeText('テスト 太郎')
            ->assertSeeText('試験 次郎')
            ->assertSeeText('【待ち】u1申請')
            ->assertSeeText('【待ち】u2申請')
            ->assertDontSeeText('【済み】混ぜ物');
    }

    public function test_all_approved_requests_are_visible_in_request_list(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $u1 = User::factory()->create([
            'name' => 'テスト 太郎',
            'email_verified_at' => now(),
        ]);
        $u2 = User::factory()->create([
            'name' => '試験 次郎',
            'email_verified_at' => now(),
        ]);

        $a1 = $this->createAttendance($u1, [
            'work_date'    => '2026-02-19',
            'clock_in_at'  => Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 19, 18, 0, 0, 'Asia/Tokyo'),
            'note'         => '元備考1',
        ]);

        $a2 = $this->createAttendance($u2, [
            'work_date'    => '2026-02-19',
            'clock_in_at'  => Carbon::create(2026, 2, 19, 10, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 19, 19, 0, 0, 'Asia/Tokyo'),
            'note'         => '元備考2',
        ]);

        $admin = Admin::create([
            'name'     => '管理者',
            'email'    => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $r1 = StampCorrectionRequest::create([
            'attendance_id' => $a1->id,
            'requested_clock_in_at'  => Carbon::create(2026, 2, 19, 9, 5, 0, 'Asia/Tokyo'),
            'requested_clock_out_at' => Carbon::create(2026, 2, 19, 18, 5, 0, 'Asia/Tokyo'),
            'requested_note' => '【済み】u1申請',
            'requested_breaks' => [],
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);
        $r1->forceFill(['status' => 'approved'])->save();

        $r2 = StampCorrectionRequest::create([
            'attendance_id' => $a2->id,
            'requested_clock_in_at'  => Carbon::create(2026, 2, 19, 10, 5, 0, 'Asia/Tokyo'),
            'requested_clock_out_at' => Carbon::create(2026, 2, 19, 19, 5, 0, 'Asia/Tokyo'),
            'requested_note' => '【済み】u2申請',
            'requested_breaks' => [],
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);
        $r2->forceFill(['status' => 'approved'])->save();

        $r3 = StampCorrectionRequest::create([
            'attendance_id' => $a1->id,
            'requested_clock_in_at'  => Carbon::create(2026, 2, 19, 8, 55, 0, 'Asia/Tokyo'),
            'requested_clock_out_at' => Carbon::create(2026, 2, 19, 17, 55, 0, 'Asia/Tokyo'),
            'requested_note' => '【待ち】混ぜ物',
            'requested_breaks' => [],
        ]);
        $r3->forceFill(['status' => 'awaiting_approval'])->save();

        $this->actingAs($admin, 'admin')
            ->get(route('stamp_correction_requests.index', ['status' => 'approved']))
            ->assertOk()
            ->assertSeeText('テスト 太郎')
            ->assertSeeText('試験 次郎')
            ->assertSeeText('【済み】u1申請')
            ->assertSeeText('【済み】u2申請')
            ->assertDontSeeText('【待ち】混ぜ物');
    }

    public function test_request_detail_shows_correct_contents_and_does_not_mix_other_requests(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $admin = Admin::create([
            'name'     => '管理者',
            'email'    => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $u1 = User::factory()->create([
            'name' => 'テスト 太郎',
            'email_verified_at' => now(),
        ]);
        $u2 = User::factory()->create([
            'name' => '試験 次郎',
            'email_verified_at' => now(),
        ]);

        $a1 = $this->createAttendance($u1, [
            'work_date'    => '2026-02-19',
            'clock_in_at'  => Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 19, 18, 0, 0, 'Asia/Tokyo'),
            'note'         => '元備考1',
        ]);

        $a2 = $this->createAttendance($u2, [
            'work_date'    => '2026-02-19',
            'clock_in_at'  => Carbon::create(2026, 2, 19, 10, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 19, 19, 0, 0, 'Asia/Tokyo'),
            'note'         => '元備考2',
        ]);

        $target = StampCorrectionRequest::create([
            'attendance_id' => $a1->id,
            'requested_clock_in_at'  => Carbon::create(2026, 2, 19, 9, 5, 0, 'Asia/Tokyo'),
            'requested_clock_out_at' => Carbon::create(2026, 2, 19, 18, 5, 0, 'Asia/Tokyo'),
            'requested_note'   => '【詳細ターゲット】u1申請',
            'requested_breaks' => [
                ['break_in_at' => '12:00', 'break_out_at' => '12:30'],
                ['break_in_at' => '15:00', 'break_out_at' => '15:15'],
            ],
        ]);
        $target->forceFill(['status' => 'awaiting_approval'])->save();

        $other = StampCorrectionRequest::create([
            'attendance_id' => $a2->id,
            'requested_clock_in_at'  => Carbon::create(2026, 2, 19, 6, 6, 0, 'Asia/Tokyo'),
            'requested_clock_out_at' => Carbon::create(2026, 2, 19, 22, 22, 0, 'Asia/Tokyo'),
            'requested_note'   => '【混入NG】別申請',
            'requested_breaks' => [
                ['break_in_at' => '11:11', 'break_out_at' => '11:22'],
            ],
        ]);
        $other->forceFill(['status' => 'awaiting_approval'])->save();

        $res = $this->actingAs($admin, 'admin')
            ->get(route('admin.requests.show', $target->id))
            ->assertOk();

        $res->assertSeeText('テスト 太郎');
        $res->assertSeeText('09:05');
        $res->assertSeeText('18:05');
        $res->assertSeeText('12:00');
        $res->assertSeeText('12:30');
        $res->assertSeeText('15:00');
        $res->assertSeeText('15:15');
        $res->assertSeeText('【詳細ターゲット】u1申請');

        $res->assertDontSeeText('【混入NG】別申請');
        $res->assertDontSeeText('06:06');
        $res->assertDontSeeText('22:22');
        $res->assertDontSeeText('11:11');
        $res->assertDontSeeText('11:22');

        $res->assertSeeText('承認');
        $res->assertSee(route('admin.requests.approve', $target->id));
    }

    public function test_approving_request_updates_attendance_and_break_times(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $admin = Admin::create([
            'name'     => '管理者',
            'email'    => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $user = User::factory()->create([
            'name' => 'テスト 太郎',
            'email_verified_at' => now(),
        ]);

        $attendance = $this->createAttendance($user, [
            'work_date'    => '2026-02-19',
            'clock_in_at'  => Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 19, 18, 0, 0, 'Asia/Tokyo'),
            'note'         => '元備考',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_in_at'   => Carbon::create(2026, 2, 19, 12, 0, 0, 'Asia/Tokyo'),
            'break_out_at'  => Carbon::create(2026, 2, 19, 12, 15, 0, 'Asia/Tokyo'),
        ]);
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_in_at'   => Carbon::create(2026, 2, 19, 15, 0, 0, 'Asia/Tokyo'),
            'break_out_at'  => Carbon::create(2026, 2, 19, 15, 10, 0, 'Asia/Tokyo'),
        ]);

        $scr = StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'requested_clock_in_at'  => Carbon::create(2026, 2, 19, 9, 5, 0, 'Asia/Tokyo'),
            'requested_clock_out_at' => Carbon::create(2026, 2, 19, 18, 5, 0, 'Asia/Tokyo'),
            'requested_note'   => '【承認後備考】更新されるべき',
            'requested_breaks' => [
                ['break_in_at' => '12:30', 'break_out_at' => '13:00'],
                ['break_in_at' => '16:00', 'break_out_at' => '16:20'],
            ],
        ]);
        $scr->forceFill(['status' => 'awaiting_approval'])->save();

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.requests.approve', $scr->id))
            ->assertStatus(302);

        $scr->refresh();

        $this->assertDatabaseHas('stamp_correction_requests', [
            'id'          => $scr->id,
            'status'      => 'approved',
            'approved_by' => $admin->id,
        ]);
        $this->assertNotNull($scr->approved_at);
        $this->assertSame(
            Carbon::now('Asia/Tokyo')->format('Y-m-d H:i:s'),
            $scr->approved_at->copy()->timezone('Asia/Tokyo')->format('Y-m-d H:i:s')
        );

        $attendance->refresh();

        $this->assertSame('09:05', $attendance->clock_in_at->timezone('Asia/Tokyo')->format('H:i'));
        $this->assertSame('18:05', $attendance->clock_out_at->timezone('Asia/Tokyo')->format('H:i'));
        $this->assertSame('【承認後備考】更新されるべき', $attendance->note);

        $this->assertDatabaseMissing('break_times', [
            'attendance_id' => $attendance->id,
            'break_in_at'   => Carbon::create(2026, 2, 19, 12, 0, 0, 'Asia/Tokyo')->format('Y-m-d H:i:s'),
            'break_out_at'  => Carbon::create(2026, 2, 19, 12, 15, 0, 'Asia/Tokyo')->format('Y-m-d H:i:s'),
        ]);

        $breaks = BreakTime::where('attendance_id', $attendance->id)
            ->orderBy('break_in_at')
            ->get();

        $this->assertCount(2, $breaks);

        $this->assertSame('12:30', $breaks[0]->break_in_at->timezone('Asia/Tokyo')->format('H:i'));
        $this->assertSame('13:00', $breaks[0]->break_out_at->timezone('Asia/Tokyo')->format('H:i'));

        $this->assertSame('16:00', $breaks[1]->break_in_at->timezone('Asia/Tokyo')->format('H:i'));
        $this->assertSame('16:20', $breaks[1]->break_out_at->timezone('Asia/Tokyo')->format('H:i'));
    }
}

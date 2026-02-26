<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\Admin;
use App\Models\StampCorrectionRequest;
use Illuminate\Support\Facades\Hash;

class DetailTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    public function test_detail_page_shows_logged_in_users_name(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'name' => 'テスト 太郎',
        ]);

        $attendance = Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => '2026-02-19',
            'clock_in_at'  => Carbon::create(2026, 2, 19, 9, 7, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 19, 18, 3, 0, 'Asia/Tokyo'),
        ]);

        $detail = $this->actingAs($user)
            ->get(route('attendances.show', $attendance->id))
            ->assertOk();

        $detail->assertSeeText($user->name);

        Carbon::setTestNow();
    }

    public function test_detail_page_shows_selected_date(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'name' => 'テスト 太郎',
        ]);

        $attendance = Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => '2026-02-19',
            'clock_in_at'  => Carbon::create(2026, 2, 19, 9, 7, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 19, 18, 3, 0, 'Asia/Tokyo'),
        ]);

        $detail = $this->actingAs($user)
            ->get(route('attendances.show', $attendance->id))
            ->assertOk();

        $detail->assertSeeText($attendance->work_date->format('Y年'));
        $detail->assertSeeText($attendance->work_date->format('n月j日'));

        Carbon::setTestNow();
    }

    public function test_detail_page_shows_clock_in_and_out_time_matches_attendance(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'name' => 'テスト 太郎',
        ]);

        $attendance = Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => '2026-02-19',
            'clock_in_at'  => Carbon::create(2026, 2, 19, 9, 7, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 19, 18, 3, 0, 'Asia/Tokyo'),
        ]);

        $detail = $this->actingAs($user)
            ->get(route('attendances.show', $attendance->id))
            ->assertOk();

        $html = $detail->getContent();

        $this->assertMatchesRegularExpression(
            '/name="clock_in_at"[^>]*value="' . preg_quote($attendance->clock_in_at->format('H:i'), '/') . '"/u',
            $html
        );

        $this->assertMatchesRegularExpression(
            '/name="clock_out_at"[^>]*value="' . preg_quote($attendance->clock_out_at->format('H:i'), '/') . '"/u',
            $html
        );

        Carbon::setTestNow();
    }

    public function test_detail_page_shows_break_in_and_out_time_matches_attendance(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'name' => 'テスト 太郎',
        ]);

        $attendance = Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => '2026-02-19',
            'clock_in_at'  => Carbon::create(2026, 2, 19, 9, 7, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 19, 18, 3, 0, 'Asia/Tokyo'),
        ]);

        $break = $attendance->breakTimes()->create([
            'break_in_at'  => Carbon::create(2026, 2, 19, 12, 7, 0, 'Asia/Tokyo'),
            'break_out_at' => Carbon::create(2026, 2, 19, 13, 3, 0, 'Asia/Tokyo'),
        ]);

        $detail = $this->actingAs($user)
            ->get(route('attendances.show', $attendance->id))
            ->assertOk();

        $html = $detail->getContent();

        $this->assertMatchesRegularExpression(
            '/name="breaks\[\d+\]\[break_in_at\]"[^>]*value="'
                . preg_quote($break->break_in_at->format('H:i'), '/')
                . '"/u',
            $html
        );

        $this->assertMatchesRegularExpression(
            '/name="breaks\[\d+\]\[break_out_at\]"[^>]*value="'
                . preg_quote($break->break_out_at->format('H:i'), '/')
                . '"/u',
            $html
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

    private function createBreakTime(Attendance $attendance, array $overrides = []): BreakTime
    {
        return $attendance->breakTimes()->create(array_merge([
            'break_in_at'  => Carbon::create(2026, 2, 19, 12, 7, 0, 'Asia/Tokyo'),
            'break_out_at' => Carbon::create(2026, 2, 19, 13, 3, 0, 'Asia/Tokyo'),
        ], $overrides));
    }

    public function test_clock_in_after_clock_out_shows_validation_message(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['email_verified_at' => now()]);
        $attendance = $this->createAttendance($user);

        $res = $this->actingAs($user)->patch(route('attendances.update', $attendance->id), [
            'clock_in_at'  => '19:00',
            'clock_out_at' => '18:00',
            'note'         => 'テスト備考',
            'breaks'        => [],
        ]);

        $res->assertStatus(302);
        $res->assertSessionHasErrors(['clock_in_at']);

        $msg = session('errors')->first('clock_in_at');

        $this->assertMatchesRegularExpression(
            '/^出勤時間(もしくは退勤時間)?が不適切な値です$/u',
            $msg
        );
        Carbon::setTestNow();
    }

    public function test_break_in_after_clock_out_shows_validation_message(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['email_verified_at' => now()]);
        $attendance = $this->createAttendance($user, []);

        $res = $this->actingAs($user)->patch(route('attendances.update', $attendance->id), [
            'clock_in_at'  => '09:07',
            'clock_out_at' => '18:03',
            'note'         => 'テスト備考',
            'breaks'       => [
                [
                    'break_in_at'  => '18:30',
                    'break_out_at' => '18:40',
                ],
            ],
        ]);

        $res->assertStatus(302);
        $res->assertSessionHasErrors(['breaks.0.break_in_at']);

        $msg = session('errors')->first('breaks.0.break_in_at');
        $this->assertSame('休憩時間が不適切な値です', $msg);

        Carbon::setTestNow();
    }

    public function test_break_out_after_clock_out_shows_validation_message(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['email_verified_at' => now()]);
        $attendance = $this->createAttendance($user);

        $res = $this->actingAs($user)->patch(route('attendances.update', $attendance->id), [
            'clock_in_at'  => '09:07',
            'clock_out_at' => '18:03',
            'note'         => 'テスト備考',
            'breaks'       => [
                [
                    'break_in_at'  => '12:00',
                    'break_out_at' => '18:40',
                ],
            ],
        ]);

        $res->assertStatus(302);
        $res->assertSessionHasErrors();

        $errors = collect(session('errors')->getBag('default')->messages())->flatten();

        $this->assertTrue(
            $errors->contains('休憩時間もしくは退勤時間が不適切な値です'),
            'Expected validation message was not found in session errors.'
        );
        Carbon::setTestNow();
    }


    public function test_note_is_required_shows_validation_message(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['email_verified_at' => now()]);
        $attendance = $this->createAttendance($user);

        $res = $this->actingAs($user)->patch(route('attendances.update', $attendance->id), [
            'clock_in_at'  => '09:07',
            'clock_out_at' => '18:03',
            'note'         => '',
            'breaks'       => [],
        ]);

        $res->assertStatus(302);
        $res->assertSessionHasErrors(['note']);

        $this->assertSame('備考を記入してください', session('errors')->first('note'));

        Carbon::setTestNow();
    }

    public function test_correction_request_is_created_and_visible_to_admin(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'name' => 'テスト 太郎',
        ]);

        $attendance = $this->createAttendance($user, [
            'work_date'    => '2026-02-19',
            'clock_in_at'  => Carbon::create(2026, 2, 19, 9, 7, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 19, 18, 3, 0, 'Asia/Tokyo'),
            'note'         => '元の備考',
        ]);

        $requestedIn  = '10:00';
        $requestedOut = '19:00';
        $requestedNote = '申請備考です';

        $res = $this->actingAs($user)->patch(route('attendances.update', $attendance->id), [
            'clock_in_at'  => $requestedIn,
            'clock_out_at' => $requestedOut,
            'note'         => $requestedNote,
            'breaks'       => [
                ['break_in_at' => '12:00', 'break_out_at' => '12:30'],
            ],
        ]);

        $res->assertStatus(302);
        $res->assertRedirect(route('attendances.show', $attendance->id));
        $res->assertSessionHas('flash_message', '修正申請を送信しました');

        $this->assertDatabaseHas('stamp_correction_requests', [
            'attendance_id'  => $attendance->id,
            'status'         => 'awaiting_approval',
            'requested_note' => $requestedNote,
        ]);

        /** @var StampCorrectionRequest $scr */
        $scr = StampCorrectionRequest::where('attendance_id', $attendance->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('2026-02-19 10:00:00', $scr->requested_clock_in_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-02-19 19:00:00', $scr->requested_clock_out_at->format('Y-m-d H:i:s'));

        $admin = Admin::create([
            'name'     => '管理者',
            'email'    => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $list = $this->actingAs($admin, 'admin')
            ->get(route('stamp_correction_requests.index', ['status' => 'awaiting_approval']))
            ->assertOk();

        $listHtml = $list->getContent();

        $this->assertStringContainsString('承認待ち', $listHtml);
        $this->assertStringContainsString($user->name, $listHtml);
        $this->assertStringContainsString($requestedNote, $listHtml);
        $this->assertStringContainsString(route('admin.requests.show', $scr->id), $listHtml);

        $show = $this->actingAs($admin, 'admin')
            ->get(route('admin.requests.show', $scr->id))
            ->assertOk();

        $showHtml = $show->getContent();

        $this->assertStringContainsString($user->name, $showHtml);
        $this->assertStringContainsString('2026年', $showHtml);
        $this->assertStringContainsString('2月19日', $showHtml);
        $this->assertStringContainsString($requestedIn, $showHtml);
        $this->assertStringContainsString($requestedOut, $showHtml);
        $this->assertStringContainsString($requestedNote, $showHtml);

        $this->assertStringContainsString(route('admin.requests.approve', $scr->id), $showHtml);

        Carbon::setTestNow();
    }

    public function test_request_list_shows_all_awaiting_requests_of_logged_in_user(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['email_verified_at' => now()]);

        $a1 = $this->createAttendance($user, [
            'work_date'    => '2026-02-19',
            'clock_in_at'  => Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 19, 18, 0, 0, 'Asia/Tokyo'),
            'note'         => '元備考1',
        ]);

        $a2 = $this->createAttendance($user, [
            'work_date'    => '2026-02-20',
            'clock_in_at'  => Carbon::create(2026, 2, 20, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 20, 18, 0, 0, 'Asia/Tokyo'),
            'note'         => '元備考2',
        ]);

        $this->actingAs($user)->patch(route('attendances.update', $a1->id), [
            'clock_in_at'  => '10:00',
            'clock_out_at' => '19:00',
            'note'         => '申請備考A',
            'breaks'       => [],
        ])->assertStatus(302);

        $this->actingAs($user)->patch(route('attendances.update', $a2->id), [
            'clock_in_at'  => '10:30',
            'clock_out_at' => '19:30',
            'note'         => '申請備考B',
            'breaks'       => [],
        ])->assertStatus(302);

        $list = $this->actingAs($user)
            ->get(route('stamp_correction_requests.index', ['status' => 'awaiting_approval']))
            ->assertOk();

        $html = $list->getContent();

        $this->assertStringContainsString('申請備考A', $html);
        $this->assertStringContainsString('申請備考B', $html);

        $this->assertStringContainsString('02/19', $html);
        $this->assertStringContainsString('02/20', $html);

        Carbon::setTestNow();
    }

    public function test_approved_tab_shows_all_admin_approved_requests_of_user(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['email_verified_at' => now(), 'name' => 'テスト 太郎']);

        $a1 = $this->createAttendance($user, [
            'work_date'    => '2026-02-19',
            'clock_in_at'  => Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 19, 18, 0, 0, 'Asia/Tokyo'),
            'note'         => '元備考1',
        ]);

        $a2 = $this->createAttendance($user, [
            'work_date'    => '2026-02-20',
            'clock_in_at'  => Carbon::create(2026, 2, 20, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 20, 18, 0, 0, 'Asia/Tokyo'),
            'note'         => '元備考2',
        ]);

        $note1 = '承認済みになる申請A';
        $note2 = '承認済みになる申請B';

        $this->actingAs($user)->patch(route('attendances.update', $a1->id), [
            'clock_in_at'  => '10:00',
            'clock_out_at' => '19:00',
            'note'         => $note1,
            'breaks'       => [],
        ])->assertStatus(302);

        $this->actingAs($user)->patch(route('attendances.update', $a2->id), [
            'clock_in_at'  => '10:30',
            'clock_out_at' => '19:30',
            'note'         => $note2,
            'breaks'       => [],
        ])->assertStatus(302);

        $req1 = StampCorrectionRequest::where('attendance_id', $a1->id)->latest('id')->firstOrFail();
        $req2 = StampCorrectionRequest::where('attendance_id', $a2->id)->latest('id')->firstOrFail();

        $admin = Admin::create([
            'name'     => '管理者',
            'email'    => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.requests.approve', $req1->id))
            ->assertStatus(302);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.requests.approve', $req2->id))
            ->assertStatus(302);

        $this->assertDatabaseHas('stamp_correction_requests', ['id' => $req1->id, 'status' => 'approved']);
        $this->assertDatabaseHas('stamp_correction_requests', ['id' => $req2->id, 'status' => 'approved']);

        $list = $this->actingAs($user)
            ->get(route('stamp_correction_requests.index', ['status' => 'approved']))
            ->assertOk();

        $html = $list->getContent();

        foreach ([$note1, $note2] as $note) {
            $pattern = '/<tr class="index__table-row">[\s\S]*?承認済み[\s\S]*?'
                . preg_quote($note, '/')
                . '[\s\S]*?<\/tr>/us';
            $this->assertMatchesRegularExpression($pattern, $html);
        }

        Carbon::setTestNow();
    }
}

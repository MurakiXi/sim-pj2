<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Attendance;
use Symfony\Component\DomCrawler\Crawler;

class AttendanceTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    public function test_clock_in_button_works_and_status_becomes_working(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('attendances.create'));
        $response->assertOk();
        $response->assertSeeText('出勤');

        $post = $this->actingAs($user)->post(route('attendances.clock_in'));

        $post->assertRedirect('/attendance');

        $after = $this->actingAs($user)->get('/attendance');
        $after->assertOk();
        $after->assertSeeText('出勤中');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
        ]);

        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', now()->toDateString())
            ->firstOrFail();

        $this->assertNotNull($attendance->clock_in_at);
        $this->assertNull($attendance->clock_out_at);

        Carbon::setTestNow();
    }

    public function test_clock_in_can_be_done_only_once_per_day(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => now()->toDateString(),
            'clock_in_at'  => now(),
            'clock_out_at' => now(),
        ]);

        $page = $this->actingAs($user)->get(route('attendances.create'));
        $page->assertOk();

        $page->assertSeeText('退勤済');

        $page->assertDontSee('action="' . route('attendances.clock_in') . '"', false);

        $post = $this->actingAs($user)->post(route('attendances.clock_in'));
        $post->assertRedirect(route('attendances.create'));

        $post->assertSessionHas('flash_message');

        $this->assertDatabaseCount('attendances', 1);

        Carbon::setTestNow();
    }

    public function test_clock_in_time_is_visible_on_attendance_list(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 7, 0, 'Asia/Tokyo'));

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->post(route('attendances.clock_in'))
            ->assertRedirect(route('attendances.create'));

        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', now()->toDateString())
            ->firstOrFail();

        $expectedTime = $attendance->clock_in_at->format('H:i');

        $this->assertNotNull($attendance->clock_in_at);

        $list = $this->actingAs($user)->get(route('attendances.index'));
        $list->assertOk();

        $list->assertSeeText($expectedTime);

        $html = $list->getContent();
        $workDate = now()->toDateString();

        $youbi = ['日', '月', '火', '水', '木', '金', '土'][now()->dayOfWeek];
        $dateLabel = now()->format('m/d') . "({$youbi})";

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $rows = $dom->getElementsByTagName('tr');

        $found = false;
        foreach ($rows as $tr) {
            $text = preg_replace('/\s+/u', ' ', trim($tr->textContent));
            if (str_contains($text, $dateLabel)) {
                $found = true;
                $this->assertStringContainsString($expectedTime, $text);
                break;
            }
        }

        $this->assertTrue($found, "勤怠一覧に日付 {$dateLabel} の行が見つかりませんでした。");

        Carbon::setTestNow();
    }

    public function test_break_in_button_works_and_status_becomes_break(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => now()->toDateString(),
            'clock_in_at'  => now(),
            'clock_out_at' => null,
        ]);

        $this->get(route('attendances.create'))
            ->assertOk()
            ->assertSeeText('休憩入');

        Carbon::setTestNow(Carbon::create(2026, 2, 19, 12, 0, 0, 'Asia/Tokyo'));

        $this->post(route('attendances.break_in'))
            ->assertRedirect('/attendance');

        $this->get('/attendance')
            ->assertOk()
            ->assertSeeText('休憩中');

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_out_at'  => null,
            'break_in_at'   => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        Carbon::setTestNow();
    }

    public function test_break_in_can_be_done_any_times(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => now()->toDateString(),
            'clock_in_at'  => now(),
            'clock_out_at' => null,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 2, 19, 12, 0, 0, 'Asia/Tokyo'));
        $this->post(route('attendances.break_in'))->assertRedirect('/attendance');

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_out_at'  => null,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 2, 19, 12, 30, 0, 'Asia/Tokyo'));
        $this->post(route('attendances.break_out'))->assertRedirect('/attendance');

        $this->assertDatabaseMissing('break_times', [
            'attendance_id' => $attendance->id,
            'break_out_at'  => null,
        ]);
        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
        ]);

        $this->get('/attendance')
            ->assertOk()
            ->assertSeeText('休憩入');

        Carbon::setTestNow();
    }

    public function test_break_out_button_works_and_status_becomes_working(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => now()->toDateString(),
            'clock_in_at'  => now(),
            'clock_out_at' => null,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 2, 19, 12, 0, 0, 'Asia/Tokyo'));
        $breakInAt = Carbon::now()->toDateTimeString();

        $this->post(route('attendances.break_in'))->assertRedirect('/attendance');

        $this->get('/attendance')
            ->assertOk()
            ->assertSeeText('休憩中')
            ->assertSeeText('休憩戻');

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_in_at'   => $breakInAt,
            'break_out_at'  => null,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 2, 19, 12, 30, 0, 'Asia/Tokyo'));
        $breakOutAt = Carbon::now()->toDateTimeString();

        $this->post(route('attendances.break_out'))->assertRedirect('/attendance');

        $this->get('/attendance')
            ->assertOk()
            ->assertSeeText('休憩入')
            ->assertSeeText('出勤中')
            ->assertDontSeeText('休憩中')
            ->assertDontSeeText('休憩戻');

        $this->assertDatabaseMissing('break_times', [
            'attendance_id' => $attendance->id,
            'break_out_at'  => null,
        ]);

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_in_at'   => $breakInAt,
            'break_out_at'  => $breakOutAt,
        ]);

        Carbon::setTestNow();
    }

    public function test_break_out_can_be_done_any_times(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => now()->toDateString(),
            'clock_in_at'  => now(),
            'clock_out_at' => null,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 2, 19, 12, 0, 0, 'Asia/Tokyo'));
        $this->post(route('attendances.break_in'))->assertRedirect('/attendance');

        Carbon::setTestNow(Carbon::create(2026, 2, 19, 12, 30, 0, 'Asia/Tokyo'));
        $this->post(route('attendances.break_out'))->assertRedirect('/attendance');

        $this->assertDatabaseMissing('break_times', [
            'attendance_id' => $attendance->id,
            'break_out_at'  => null,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 2, 19, 13, 0, 0, 'Asia/Tokyo'));
        $this->post(route('attendances.break_in'))->assertRedirect('/attendance');

        $this->get('/attendance')
            ->assertOk()
            ->assertSeeText('休憩戻');

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_out_at'  => null,
        ]);

        Carbon::setTestNow();
    }


    public function test_break_time_is_visible_on_attendance_list(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => now()->toDateString(),
            'clock_in_at'  => now(),
            'clock_out_at' => null,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 2, 19, 12, 0, 0, 'Asia/Tokyo'));
        $breakInAt = Carbon::now()->toDateTimeString();

        $this->post(route('attendances.break_in'))->assertRedirect('/attendance');

        $this->get('/attendance')
            ->assertOk()
            ->assertSeeText('休憩中')
            ->assertSeeText('休憩戻');

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_in_at'   => $breakInAt,
            'break_out_at'  => null,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 2, 19, 12, 30, 0, 'Asia/Tokyo'));
        $breakOutAt = Carbon::now()->toDateTimeString();

        $this->post(route('attendances.break_out'))->assertRedirect('/attendance');

        $this->get('/attendance')
            ->assertOk()
            ->assertSeeText('休憩入')
            ->assertDontSeeText('休憩中');

        $list = $this->get('/attendance/list')->assertOk();

        $list->assertSeeText('2026/02');

        $dateLabel = Carbon::parse('2026-02-19')->locale('ja')->isoFormat('MM/DD(ddd)');

        $content = $list->getContent();

        $crawler = new Crawler($content);

        $rows = $crawler->filter('tr')->reduce(function (Crawler $tr) {
            $text = preg_replace('/\s+/u', '', $tr->text());
            return str_contains($text, '02/19');
        });

        $this->assertGreaterThan(0, $rows->count(), '02/19の行が見つかりません');

        $rowText = preg_replace('/\s+/u', '', $rows->first()->text());

        $expectedBreak = '0:30';
        $this->assertStringContainsString($expectedBreak, $rowText);

        dump($rowText);

        $content = $list->getContent();
        $this->assertMatchesRegularExpression(
            '/<tr[^>]*>.*' . preg_quote($dateLabel, '/') . '.*' . preg_quote($expectedBreak, '/') . '.*<\/tr>/s',
            $content
        );
        Carbon::setTestNow();
    }

    public function test_clock_out_button_works_and_status_becomes_off(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => now()->toDateString(),
            'clock_in_at'  => now(),
            'clock_out_at' => null,
        ]);

        $this->get(route('attendances.create'))
            ->assertOk()
            ->assertSeeText('退勤');

        Carbon::setTestNow(Carbon::create(2026, 2, 19, 12, 0, 0, 'Asia/Tokyo'));

        $this->post(route('attendances.clock_out'))
            ->assertRedirect('/attendance');

        $this->get('/attendance')
            ->assertOk()
            ->assertSeeText('退勤済');

        $this->assertDatabaseHas('attendances', [
            'id'           => $attendance->id,
            'clock_out_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        Carbon::setTestNow();
    }


    public function test_clock_out_time_is_visible_on_attendance_list(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 7, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $this->post(route('attendances.clock_in'))
            ->assertRedirect(route('attendances.create'));

        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', now()->toDateString())
            ->firstOrFail();

        $this->assertNotNull($attendance->clock_in_at);

        Carbon::setTestNow(Carbon::create(2026, 2, 19, 12, 0, 0, 'Asia/Tokyo'));
        $expectedTime = Carbon::now()->format('H:i'); // 12:00

        $this->post(route('attendances.clock_out'))
            ->assertRedirect('/attendance');

        $this->get('/attendance')
            ->assertOk()
            ->assertSeeText('退勤済');

        $this->assertDatabaseHas('attendances', [
            'id'           => $attendance->id,
            'clock_out_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        $list = $this->get(route('attendances.index'))->assertOk();
        $list->assertSeeText($expectedTime);

        $html = $list->getContent();

        $date = Carbon::create(2026, 2, 19, 0, 0, 0, 'Asia/Tokyo');
        $youbi = ['日', '月', '火', '水', '木', '金', '土'][$date->dayOfWeek];
        $dateLabel = $date->format('m/d') . "({$youbi})"; // 02/19(木)

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();

        $rows = $dom->getElementsByTagName('tr');

        $found = false;
        foreach ($rows as $tr) {
            $text = preg_replace('/\s+/u', ' ', trim($tr->textContent));
            if (str_contains($text, $dateLabel)) {
                $found = true;
                $this->assertStringContainsString($expectedTime, $text);
                break;
            }
        }

        $this->assertTrue($found, "勤怠一覧に日付 {$dateLabel} の行が見つかりませんでした。");

        Carbon::setTestNow();
    }
}

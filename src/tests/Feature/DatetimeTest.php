<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Models\User;

class DatetimeTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    public function test_clock_screen_shows_current_datetime_in_ui_format(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 5, 0, 'Asia/Tokyo'));

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertOk();

        $html = $response->getContent();
        $now  = now();

        preg_match('/<div class="create__date-label">\s*([^<]+)\s*<\/div>/u', $html, $m);
        $this->assertNotEmpty($m, 'create__date-label が見つかりません');
        $dateText = trim($m[1]);

        $this->assertMatchesRegularExpression(
            '/^\d{4}年\d{1,2}月\d{1,2}日\([日月火水木金土]\)$/u',
            $dateText
        );

        preg_match('/^(\d{4})年(\d{1,2})月(\d{1,2})日\(([日月火水木金土])\)$/u', $dateText, $d);
        $youbi = ['日', '月', '火', '水', '木', '金', '土'][$now->dayOfWeek];

        $this->assertSame((int)$now->year,  (int)$d[1]);
        $this->assertSame((int)$now->month, (int)$d[2]);
        $this->assertSame((int)$now->day,   (int)$d[3]);
        $this->assertSame($youbi, $d[4]);

        preg_match('/<div class="create__time-label">\s*([^<]+)\s*<\/div>/u', $html, $m2);
        $this->assertNotEmpty($m2, 'create__time-label が見つかりません');
        $timeText = trim($m2[1]);

        $this->assertMatchesRegularExpression('/^\d{1,2}:\d{2}$/', $timeText);

        preg_match('/^(\d{1,2}):(\d{2})$/', $timeText, $t);
        $this->assertSame((int)$now->hour,   (int)$t[1]);
        $this->assertSame((int)$now->minute, (int)$t[2]);

        $expectedMs = $now->timestamp * 1000;
        $this->assertStringContainsString('data-server-now="' . $expectedMs . '"', $html);

        Carbon::setTestNow();
    }
}

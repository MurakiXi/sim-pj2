<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Models\User;
use Symfony\Component\DomCrawler\Crawler;

class DatetimeTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    public function test_clock_screen_shows_current_datetime_in_ui_format(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 19, 9, 5, 0, 'Asia/Tokyo'));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertOk();

        $html = $response->getContent();
        $now  = now('Asia/Tokyo');

        $crawler = new Crawler($html);

        // date
        $dateNode = $crawler->filter('.create__date-label');
        $this->assertGreaterThan(0, $dateNode->count(), 'create__date-label が見つかりません');
        $dateText = trim($dateNode->text());

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

        // time
        $timeNode = $crawler->filter('.create__time-label');
        $this->assertGreaterThan(0, $timeNode->count(), 'create__time-label が見つかりません');
        $timeText = trim($timeNode->text());

        $this->assertMatchesRegularExpression('/^\d{1,2}:\d{2}$/', $timeText);

        preg_match('/^(\d{1,2}):(\d{2})$/', $timeText, $t);
        $this->assertSame((int)$now->hour,   (int)$t[1]);
        $this->assertSame((int)$now->minute, (int)$t[2]);

        $expectedMs = $now->timestamp * 1000;
        $this->assertStringContainsString('data-server-now="' . $expectedMs . '"', $html);

        Carbon::setTestNow();
    }
}

<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;

class StampDateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 打刻画面を開いた際、現在の日時情報がUIと同じ形式で出力されていることの確認
     */
    public function test_current_date_time_is_displayed_correctly()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 現在時刻を固定する（秒の切り替わりによる失敗を防ぐ）
        $knownDate = Carbon::create(2026, 1, 1, 10, 0, 0);
        Carbon::setTestNow($knownDate);

        $expectedDate = '2026年1月1日(木)';
        $expectedTime = '10:00';

        $response = $this->get('/attendance');

        $response->assertStatus(200);

        // 画面上に指定の形式で表示されているか確認
        $response->assertSee($expectedDate);
        $response->assertSee($expectedTime);

        // テスト終了後に時間を元に戻す
        Carbon::setTestNow();
    }
}

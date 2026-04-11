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

        $now = Carbon::now();
        $expectedDate = $now->isoFormat('YYYY年M月D日(ddd)');
        $expectedTime = $now->format('H:i');

        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee($expectedDate);
        $response->assertSee($expectedTime);
    }
}

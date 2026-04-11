<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 退勤ボタンが正しく機能し、ステータスが「退勤済」に変更されることの確認
     */
    public function test_clock_out_button_works_correctly()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => '出勤中',
        ]);

        $response = $this->post('/attendance/clock-out');

        $response->assertRedirect('/attendance');
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => '退勤済',
        ]);
    }

    /**
     * 退勤時刻が勤怠一覧画面で正確に確認できることの確認
     */
    public function test_clock_out_time_is_recorded_in_list()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => '出勤中',
        ]);

        $now = Carbon::now();
        $this->post('/attendance/clock-out');

        $response = $this->get('/attendance/list');
        $response->assertSee($now->format('H:i'));
    }
}

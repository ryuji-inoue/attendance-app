<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class ClockInTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 出勤ボタンが正しく機能し、ステータスが「出勤中」に変更されることの確認
     */
    public function test_clock_in_button_works_correctly()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/attendance/clock-in');

        $response->assertRedirect('/attendance');
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => '出勤中',
        ]);
    }

    /**
     * 出勤は一日一回のみ可能であり、退勤後は「出勤」ボタンが表示されないことの確認
     */
    public function test_clock_in_button_hides_after_clock_out()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => '退勤済',
            'date' => Carbon::now()->toDateString(),
        ]);

        $response = $this->get('/attendance');

        $response->assertDontSee('出勤');
    }

    /**
     * 出勤時刻が勤怠一覧画面で正確に確認できることの確認
     */
    public function test_clock_in_time_is_recorded_in_list()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $now = Carbon::now();

        $this->post('/attendance/clock-in');

        $response = $this->get('/attendance/list');
        $response->assertSee($now->format('H:i'));
    }
}

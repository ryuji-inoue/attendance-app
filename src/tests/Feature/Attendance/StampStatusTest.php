<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;

class StampStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤務外の場合、勤怠ステータスが「勤務外」と正しく表示されることの確認
     */
    public function test_status_is_off_work_initially()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertSee('勤務外');
    }

    /**
     * 出勤中の場合、勤怠ステータスが「出勤中」と正しく表示されることの確認
     */
    public function test_status_is_working_after_clock_in()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => '出勤中',
        ]);

        $response = $this->get('/attendance');

        $response->assertSee('出勤中');
    }

    /**
     * 休憩中の場合、勤怠ステータスが「休憩中」と正しく表示されることの確認
     */
    public function test_status_is_on_break()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => '休憩中',
        ]);

        $response = $this->get('/attendance');

        $response->assertSee('休憩中');
    }

    /**
     * 退勤済の場合、勤怠ステータスが「退勤済」と正しく表示されることの確認
     */
    public function test_status_is_finished_after_clock_out()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => '退勤済',
        ]);

        $response = $this->get('/attendance');

        $response->assertSee('退勤済');
    }
}

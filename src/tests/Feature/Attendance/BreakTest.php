<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class BreakTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 「休憩入」ボタンが正しく機能し、ステータスが「休憩中」に変更されることの確認
     */
    public function test_break_start_button_works_correctly()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => '出勤中',
        ]);

        $response = $this->post('/attendance/break-start');

        $response->assertRedirect('/attendance');
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => '休憩中',
        ]);
    }

    /**
     * 休憩戻後に、再度「休憩入」ボタンが表示され、一日に何回でも休憩できることの確認
     */
    public function test_multiple_breaks_per_day()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => '出勤中',
        ]);

        // 1回目の休憩入
        $this->post('/attendance/break-start');
        // 1回目の休憩戻
        $this->post('/attendance/break-end');

        // 再度「休憩入」ボタンが表示されるか
        $response = $this->get('/attendance');
        $response->assertSee('休憩入');
    }

    /**
     * 「休憩戻」ボタンが正しく機能し、ステータスが「出勤中」に変更されることの確認
     */
    public function test_break_end_button_works_correctly()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => '休憩中',
        ]);
        BreakTime::factory()->create(['attendance_id' => $attendance->id, 'break_end' => null]);

        $response = $this->post('/attendance/break-end');

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => '出勤中',
        ]);
    }

    /**
     * 休憩時刻が勤怠一覧画面で正確に確認できることの確認
     */
    public function test_break_times_are_recorded_in_list()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $attendance = Attendance::factory()->create(['user_id' => $user->id, 'status' => '出勤中', 'date' => now()->toDateString()]);

        $this->post('/attendance/break-start');
        $this->post('/attendance/break-end');

        $response = $this->get('/attendance/list');
        $response->assertSee('0:00'); // 短時間の休憩
    }
}

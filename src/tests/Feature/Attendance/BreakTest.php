<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;

class BreakTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 休憩ボタンが正しく機能し、ステータスが「休憩中」になることの確認
     */
    public function test_break_start_button_works_correctly()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => '出勤中',
        ]);

        // 休憩開始処理
        $response = $this->post('/attendance/break-start');

        $response->assertRedirect('/attendance');
        // 画面上のステータス確認
        $this->get('/attendance')->assertSee('休憩中');
        // DBのステータス確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => '休憩中',
        ]);
    }

    /**
     * 休憩は一日に何回でもできることの確認（1回戻った後に「休憩入」が再表示されるか）
     */
    public function test_break_start_button_is_displayed_after_returning()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create(['user_id' => $user->id, 'status' => '出勤中']);

        // 1回目の休憩を行って戻る
        $this->post('/attendance/break-start');
        $this->post('/attendance/break-end');

        // 再度「休憩入」ボタンが表示されるか
        $response = $this->get('/attendance');
        $response->assertSee('休憩入');
    }

    /**
     * 休憩戻ボタンが正しく機能し、ステータスが「出勤中」に変更されることの確認
     */
    public function test_break_end_button_works_correctly()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => '休憩中',
        ]);
        // 終わっていない休憩データを作成
        BreakTime::factory()->create(['attendance_id' => $attendance->id, 'break_end' => null]);

        // 休憩戻処理
        $response = $this->post('/attendance/break-end');

        $response->assertRedirect('/attendance');
        // 画面上のステータス確認
        $this->get('/attendance')->assertSee('出勤中');
        // DBのステータス確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => '出勤中',
        ]);
    }

    /**
     * 休憩戻は一日に何回でもできることの確認（2回目の休憩中も「休憩戻」が表示されるか）
     */
    public function test_break_end_button_is_displayed_during_second_break()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Attendance::factory()->create(['user_id' => $user->id, 'status' => '出勤中']);

        // 1回目の休憩・戻り
        $this->post('/attendance/break-start');
        $this->post('/attendance/break-end');

        // 2回目の休憩開始
        $this->post('/attendance/break-start');

        // 2回目でも「休憩戻」ボタンが表示されるか
        $response = $this->get('/attendance');
        $response->assertSee('休憩戻');
    }

    /**
     * 休憩時刻が勤怠一覧画面で正確に確認できることの確認
     */
    public function test_break_times_are_recorded_in_list()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => '出勤中',
            'date' => now()->toDateString()
        ]);

        $this->post('/attendance/break-start');
        $this->post('/attendance/break-end');

        $response = $this->get('/attendance/list');
        // 合計休憩時間（0:00等）が表示されているか確認
        $response->assertSee('0:00');
    }
}
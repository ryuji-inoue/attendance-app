<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. 勤怠詳細画面の「名前」がログインユーザーの氏名になっていることの確認
     */
    public function test_detail_page_displays_user_name()
    {
        $user = User::factory()->create(['name' => 'テスト会員']);
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);
        $response = $this->get('/attendance/detail/' . $attendance->id);

        $response->assertSee('テスト会員');
    }

    /**
     * 2. 勤怠詳細画面の「日付」が選択した日付になっていることの確認
     */
    public function test_detail_page_displays_correct_date()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-04-01'
        ]);

        $this->actingAs($user);
        $response = $this->get('/attendance/detail/' . $attendance->id);

        // UIの表示形式に合わせて検証（2024年4月1日の形式を想定）
        $response->assertSee('2024年');
        $response->assertSee('4月1日');
    }

    /**
     * 3. 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致していることの確認
     */
    public function test_detail_page_displays_correct_clock_in_out_times()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:05:00',
            'clock_out' => '18:20:00',
        ]);

        $this->actingAs($user);
        $response = $this->get('/attendance/detail/' . $attendance->id);

        $response->assertSee('09:05');
        $response->assertSee('18:20');
    }

    /**
     * 4. 「休憩」にて記されている時間がログインユーザーの打刻と一致していることの確認
     */
    public function test_detail_page_displays_correct_break_times()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        // 休憩データの作成
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:15:00',
            'break_end' => '13:15:00',
        ]);

        $this->actingAs($user);
        $response = $this->get('/attendance/detail/' . $attendance->id);

        $response->assertSee('12:15');
        $response->assertSee('13:15');
    }
}
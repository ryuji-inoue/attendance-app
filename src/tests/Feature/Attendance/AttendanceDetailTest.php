<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤怠詳細画面に表示される氏名、日付、打刻時間が正確であることの確認
     */
    public function test_detail_page_displays_correct_info()
    {
        $user = User::factory()->create(['name' => 'テスト会員']);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-04-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:15:00',
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance/detail/' . $attendance->id);

        $response->assertSee('テスト会員');
        $response->assertSee('2024年');
        $response->assertSee('4月1日');
        $response->assertSee('09:00');
        $response->assertSee('18:15');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}

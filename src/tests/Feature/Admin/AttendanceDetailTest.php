<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤怠詳細画面に表示されるデータが、選択したものと一致していることの確認
     */
    public function test_admin_can_see_correct_attendance_detail()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['name' => '対象ユーザー']);
        $attendance = Attendance::factory()->create(['user_id' => $user->id, 'date' => '2024-04-01']);
        $this->actingAs($admin);

        $response = $this->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee('対象ユーザー');
        $response->assertSeeInOrder(['2024年', '4月1日']);
    }

    /**
     * 出勤時間が退勤時間より後になっている場合、管理者用のエラーメッセージが表示されることの確認
     */
    public function test_admin_validation_clock_in_after_clock_out()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($admin);

        $response = $this->post('/attendance/detail/' . $attendance->id, [
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'note' => '修正理由',
        ]);

        $response->assertSessionHasErrors(['clock_out' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    /**
     * 備考欄が未入力の場合、エラーメッセージが表示されることの確認
     */
    public function test_admin_validation_note_is_required()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($admin);

        $response = $this->post('/attendance/detail/' . $attendance->id, [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'note' => '',
        ]);

        $response->assertSessionHasErrors(['note' => '備考を記入してください']);
    }
}

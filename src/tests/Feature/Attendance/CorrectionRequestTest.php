<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;

class CorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示されることの確認
     */
    public function test_validation_clock_in_after_clock_out()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->post('/attendance/detail/' . $attendance->id, [
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'note' => '修正理由',
        ]);

        $response->assertSessionHasErrors(['clock_out' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    /**
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示されることの確認
     */
    public function test_validation_break_start_after_clock_out()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->post('/attendance/detail/' . $attendance->id, [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'note' => '修正理由',
            'breaks' => [['start' => '18:30', 'end' => '19:00']]
        ]);

        $response->assertSessionHasErrors(['breaks.0.start' => '休憩時間が不適切な値です']);
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示されることの確認
     */
    public function test_validation_break_end_after_clock_out()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->post('/attendance/detail/' . $attendance->id, [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'note' => '修正理由',
            'breaks' => [['start' => '12:00', 'end' => '19:00']]
        ]);

        $response->assertSessionHasErrors(['breaks.0.end' => '休憩時間もしくは退勤時間が不適切な値です']);
    }

    /**
     * 備考欄が未入力の場合のエラーメッセージが表示されることの確認
     */
    public function test_validation_note_is_required()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->post('/attendance/detail/' . $attendance->id, [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'note' => '',
        ]);

        $response->assertSessionHasErrors(['note' => '備考を記入してください']);
    }

    /**
     * 修正申請処理が実行されることの確認（管理者画面での確認準備）
     */
    public function test_correction_request_is_stored_correctly()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $this->post('/attendance/detail/' . $attendance->id, [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'note' => '修正します',
        ]);

        $this->assertDatabaseHas('attendance_correction_requests', [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending', // 承認待ち状態
        ]);
    }

    /**
     * 「承認待ち」にログインユーザーが行った申請が全て表示されていることの確認
     */
    public function test_user_can_see_own_pending_requests()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        AttendanceCorrectionRequest::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => 'pending'
        ]);

        $response = $this->get('/stamp_correction_request/list?status=pending');

        $response->assertStatus(200);
        $this->assertCount(2, $response->viewData('requests'));
    }

    /**
     * 「承認済み」に管理者が承認した修正申請が全て表示されていることの確認
     */
    public function test_user_can_see_own_approved_requests()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        AttendanceCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved'
        ]);

        $response = $this->get('/stamp_correction_request/list?status=approved');

        $response->assertSee('承認済み');
        $this->assertCount(1, $response->viewData('requests'));
    }

    /**
     * 各申請の「詳細」を押下すると勤怠詳細画面に遷移することの確認
     */
    public function test_correction_request_detail_link_works()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $request = AttendanceCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id
        ]);

        $this->actingAs($user);
        $response = $this->get('/stamp_correction_request/list');

        // 詳細画面へのリンクが存在するか
        $response->assertSee('/attendance/detail/' . $attendance->id);
    }
}
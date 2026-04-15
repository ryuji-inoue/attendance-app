<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;

class CorrectionApprovalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 修正申請一覧ページで「承認待ち」の申請が全て表示されていることの確認
     */
    public function test_admin_can_see_all_pending_requests_in_list()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['name' => '申請ユーザー']);
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        AttendanceCorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin);
        $response = $this->get('/stamp_correction_request/list?status=pending');

        $response->assertStatus(200);
        $response->assertSee('申請ユーザー');
    }

    /**
     * 修正申請一覧ページで「承認済み」の申請が全て表示されていることの確認
     */
    public function test_admin_can_see_all_approved_requests_in_list()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['name' => '承認済ユーザー']);
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        AttendanceCorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        $this->actingAs($admin);
        $response = $this->get('/stamp_correction_request/list?status=approved');

        $response->assertStatus(200);
        $response->assertSee('承認済ユーザー');
    }

    /**
     * 修正申請の詳細画面を開いた際、申請内容が正しく表示されていることの確認
     */
    public function test_admin_can_see_correction_request_details_correctly()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['name' => '申請者名']);
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        $request = AttendanceCorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'note' => '詳細確認テスト',
            'status' => 'pending',
        ]);

        $this->actingAs($admin);
        // 詳細画面のURLを想定
        $response = $this->get('/stamp_correction_request/approve/' . $request->id);

        $response->assertStatus(200);
        $response->assertSee('申請者名');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('詳細確認テスト');
    }

    /**
     * 修正申請の詳細画面で「承認」ボタンを押した際、承認処理が正しく行われることの確認
     */
    public function test_admin_approval_process_updates_attendance_and_request_status()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $attendance = Attendance::factory()->create([
            'clock_in' => '09:00',
            'clock_out' => '18:00'
        ]);

        $request = AttendanceCorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'status' => 'pending',
        ]);

        $this->actingAs($admin);
        $response = $this->post('/stamp_correction_request/approve/' . $request->id);

        // リダイレクトの確認
        $response->assertRedirect('/stamp_correction_request/list');

        // 申請ステータスが更新されているか
        $this->assertDatabaseHas('attendance_correction_requests', [
            'id' => $request->id,
            'status' => 'approved',
        ]);

        // 元の勤怠データが申請内容で更新されているか
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);
    }
}
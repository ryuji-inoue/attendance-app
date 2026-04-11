<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\CorrectionBreak;
use App\Models\BreakTime;
use Carbon\Carbon;

class CorrectionApprovalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 承認待ちの修正申請が全て表示されていることの確認
     */
    public function test_admin_can_see_pending_requests()
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
     * 承認済みの修正申請が全て表示されていることの確認
     */
    public function test_admin_can_see_approved_requests()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        AttendanceCorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'approved',
        ]);
        $this->actingAs($admin);

        $response = $this->get('/stamp_correction_request/list?status=approved');

        $response->assertStatus(200);
        $this->assertCount(1, $response->viewData('requests'));
    }

    /**
     * 修正申請の承認処理が正しく行われ、勤怠情報が更新されることの確認
     */
    public function test_admin_approval_updates_attendance_correctly()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $attendance = Attendance::factory()->create(['clock_in' => '09:00', 'clock_out' => '18:00']);
        $request = AttendanceCorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'status' => 'pending',
        ]);
        $this->actingAs($admin);

        $response = $this->post('/stamp_correction_request/approve/' . $request->id);

        $response->assertRedirect('/stamp_correction_request/list');
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);
    }
}

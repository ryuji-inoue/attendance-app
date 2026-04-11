<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者が全一般ユーザーの「氏名」「メールアドレス」を確認できることの確認
     */
    public function test_admin_can_see_staff_list()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user', 'name' => '一般スタッフ', 'email' => 'staff@example.com']);
        $this->actingAs($admin);

        $response = $this->get('/admin/staff/list');

        $response->assertStatus(200);
        $response->assertSee('一般スタッフ');
        $response->assertSee('staff@example.com');
    }

    /**
     * 選択したユーザーの勤怠情報が正しく表示されることの確認
     */
    public function test_admin_can_see_staff_attendance_history()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
        ]);
        $this->actingAs($admin);

        $response = $this->get('/admin/attendance/staff/' . $user->id);

        $response->assertStatus(200);
        $response->assertSee('09:00');
    }

    /**
     * スタッフ別勤怠一覧で「前月」を押下した時に情報の月が切り替わることの確認
     */
    public function test_staff_history_previous_month_navigation()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($admin);
        $prevMonth = now()->subMonth();

        $response = $this->get('/admin/attendance/staff/' . $user->id . '?month=' . $prevMonth->format('Y-m'));

        $response->assertSee($prevMonth->format('Y/m'));
    }
}

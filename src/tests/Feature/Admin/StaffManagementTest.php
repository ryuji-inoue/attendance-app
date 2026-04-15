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
     * 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できることの確認
     */
    public function test_admin_can_see_all_staff_members_in_list()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'role' => 'user',
            'name' => '一般スタッフ',
            'email' => 'staff@example.com'
        ]);

        $this->actingAs($admin);
        $response = $this->get('/admin/staff/list');

        $response->assertStatus(200);
        $response->assertSee('一般スタッフ');
        $response->assertSee('staff@example.com');
    }

    /**
     * 選択したユーザーの勤怠情報が正しく表示されることの確認
     */
    public function test_admin_can_see_selected_staff_attendance_history()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
        ]);

        $this->actingAs($admin);
        // 管理者用のユーザー別勤怠一覧URLを想定
        $response = $this->get('/admin/attendance/staff/' . $user->id);

        $response->assertStatus(200);
        $response->assertSee('09:00');
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示されることの確認
     */
    public function test_admin_can_navigate_to_previous_month_for_staff()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($admin);

        $prevMonth = now()->subMonth();

        $response = $this->get('/admin/attendance/staff/' . $user->id . '?month=' . $prevMonth->format('Y-m'));

        $response->assertSee($prevMonth->format('Y/m'));
    }

    /**
     * 「翌月」を押下した時に表示月の翌月の情報が表示されることの確認
     */
    public function test_admin_can_navigate_to_next_month_for_staff()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($admin);

        $nextMonth = now()->addMonth();

        $response = $this->get('/admin/attendance/staff/' . $user->id . '?month=' . $nextMonth->format('Y-m'));

        $response->assertSee($nextMonth->format('Y/m'));
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移することの確認
     */
    public function test_admin_can_transition_to_staff_attendance_detail()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        $this->actingAs($admin);
        $response = $this->get('/admin/attendance/staff/' . $user->id);

        // 管理者が詳細画面へ遷移するリンクを確認
        $response->assertSee('/attendance/detail/' . $attendance->id);
    }
}
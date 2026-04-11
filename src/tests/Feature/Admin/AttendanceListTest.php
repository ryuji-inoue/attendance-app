<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * その日になされた全ユーザーの勤怠情報が正確に確認できることの確認
     */
    public function test_admin_can_see_all_users_attendance()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create(['name' => 'ユーザーA']);
        $user2 = User::factory()->create(['name' => 'ユーザーB']);
        
        Attendance::factory()->create(['user_id' => $user1->id, 'date' => now()->toDateString()]);
        Attendance::factory()->create(['user_id' => $user2->id, 'date' => now()->toDateString()]);

        $this->actingAs($admin);

        $response = $this->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('ユーザーA');
        $response->assertSee('ユーザーB');
    }

    /**
     * 勤怠一覧画面に遷移した際に、現在の日付が表示されていることの確認
     */
    public function test_current_date_is_displayed_by_default()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->get('/admin/attendance/list');

        $response->assertSee(now()->format('Y/m/d'));
    }

    /**
     * 「前日」を押下した時に、前の日の勤怠情報が表示されることの確認
     */
    public function test_navigation_to_previous_day()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $prevDay = now()->subDay();

        $response = $this->get('/admin/attendance/list?date=' . $prevDay->toDateString());

        $response->assertSee($prevDay->format('Y/m/d'));
    }

    /**
     * 「翌日」を押下した時に、次の日の勤怠情報が表示されることの確認
     */
    public function test_navigation_to_next_day()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $nextDay = now()->addDay();

        $response = $this->get('/admin/attendance/list?date=' . $nextDay->toDateString());

        $response->assertSee($nextDay->format('Y/m/d'));
    }
}

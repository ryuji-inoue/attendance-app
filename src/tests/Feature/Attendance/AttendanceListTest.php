<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 自分が行った勤怠情報が全て表示されていることの確認
     */
    public function test_user_can_see_only_own_attendance()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        
        Attendance::factory()->create(['user_id' => $user->id, 'date' => now()->toDateString()]);
        Attendance::factory()->create(['user_id' => $otherUser->id, 'date' => now()->toDateString()]);

        $this->actingAs($user);

        $response = $this->get('/attendance/list');
        
        $response->assertStatus(200);
        $this->assertCount(1, $response->viewData('attendances'));
    }

    /**
     * 勤怠一覧画面に遷移した際に、現在の日付（月）が表示されていることの確認
     */
    public function test_current_month_is_displayed_by_default()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/attendance/list');

        $response->assertSee(now()->format('Y/m'));
    }

    /**
     * 「前月」ボタンを押下した時に、表示月の前月の情報が表示されることの確認
     */
    public function test_previous_month_navigation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $prevMonth = now()->subMonth();

        $response = $this->get('/attendance/list?month=' . $prevMonth->format('Y-m'));

        $response->assertSee($prevMonth->format('Y/m'));
    }

    /**
     * 「翌月」ボタンを押下した時に、表示月の翌月の情報が表示されることの確認
     */
    public function test_next_month_navigation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $nextMonth = now()->addMonth();

        $response = $this->get('/attendance/list?month=' . $nextMonth->format('Y-m'));

        $response->assertSee($nextMonth->format('Y/m'));
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移することの確認
     */
    public function test_detail_link_redirects_to_attendance_detail()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->get('/attendance/list');

        $response->assertSee('/attendance/detail/' . $attendance->id);
    }
}

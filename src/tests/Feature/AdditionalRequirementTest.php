<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceCorrectionRequest;
use Carbon\Carbon;

class AdditionalRequirementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * FN004 / FN010：認証画面間の遷移
     * 会員登録画面からログイン画面、ログインから会員登録へのリンクが存在するか
     */
    public function test_auth_pages_have_cross_links()
    {
        $responseLogin = $this->get('/login');
        $responseLogin->assertStatus(200);
        $responseLogin->assertSee('/register');

        $responseRegister = $this->get('/register');
        $responseRegister->assertStatus(200);
        $responseRegister->assertSee('/login');
    }

    /**
     * FN011-2：未認証でのログインブロック
     * 新規会員登録後、メール認証を完了せずにログイン（保護された画面にアクセス）した場合、
     * メール認証誘導画面へ遷移するか。
     */
    public function test_unverified_user_is_redirected_to_verify_email_screen()
    {
        // email_verified_at を null（未認証）にしてユーザーを作成
        // ※ Userファクトリに unverified() 状態が定義されている前提です
        $user = User::factory()->unverified()->create(['role' => 'user']);

        // ログイン状態で、打刻画面（verifiedミドルウェアで保護されている想定）へアクセス
        $response = $this->actingAs($user)->get('/attendance');

        // Laravel/Fortifyの標準仕様である、メール認証誘導画面へのリダイレクトを検証
        $response->assertRedirect('/email/verify');
    }

    /**
     * FN012：認証メール再送機能
     * メール認証誘導画面で再送処理を行った際、認証メールが再送信されるか。
     */
    public function test_verification_email_can_be_resent()
    {
        // 通知処理をモック化（実際に外部へメールを飛ばさず、送信された「事実」だけを記録する）
        \Illuminate\Support\Facades\Notification::fake();

        // 未認証ユーザーを作成
        $user = User::factory()->unverified()->create(['role' => 'user']);

        // Fortifyの標準的なメール再送エンドポイントへPOSTリクエスト
        $response = $this->actingAs($user)->post('/email/verification-notification');

        // 処理成功（リダイレクト）と、フラッシュメッセージ（status）がセットされているかを検証
        $response->assertRedirect();
        $response->assertSessionHas('status', 'verification-link-sent');

        // VerifyEmail（認証メール）の通知クラスが、指定したユーザーに正しく送信されたかを検証
        \Illuminate\Support\Facades\Notification::assertSentTo(
            [$user],
            \Illuminate\Auth\Notifications\VerifyEmail::class
        );
    }

    /**
     * FN013 / FN017：ログアウト機能
     * 一般ユーザーと管理者が正常にログアウトできるか
     */
    public function test_user_and_admin_can_logout()
    {
        // 一般ユーザーのログアウト
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/login'); // 【修正】Fortifyのデフォルトに合わせて /login に変更
        $this->assertGuest();

        // 管理者のログアウト
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->post('/logout')
            ->assertRedirect('/login'); // 【修正】こちらも同様
        $this->assertGuest();
    }

    /**
     * FN022-3：退勤時のフラッシュメッセージ
     * 「お疲れ様でした。」が表示されるか
     */
    public function test_clock_out_displays_flash_message()
    {
        $user = User::factory()->create(['role' => 'user']);
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => '09:00:00',
            'status' => '出勤中',
        ]);

        $response = $this->actingAs($user)->post('/attendance/clock-out');

        $response->assertRedirect('/attendance');
        $response->assertSessionHas('success', 'お疲れ様でした。');
    }

    /**
     * FN026-4：詳細画面の休憩入力フィールド
     * 取得済みの休憩回数 + 1つの空入力フィールドが表示されるか
     */
    public function test_detail_page_shows_n_plus_one_break_inputs()
    {
        $user = User::factory()->create(['role' => 'user']);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        BreakTime::create(['attendance_id' => $attendance->id, 'break_start' => '12:00:00', 'break_end' => '12:30:00']);
        BreakTime::create(['attendance_id' => $attendance->id, 'break_start' => '15:00:00', 'break_end' => '15:15:00']);

        $response = $this->actingAs($user)->get("/attendance/detail/{$attendance->id}");

        $response->assertStatus(200);

        $displayData = $response->viewData('displayData');
        $this->assertCount(3, $displayData['breaks']);
    }

    /**
     * FN027 / FN038：承認待ち状態の編集ロック
     * メッセージが表示され、編集不可のロジックが働いているか
     */
    public function test_pending_correction_locks_editing()
    {
        // 【デバッグ用】もしこのテストが失敗した場合、ターミナルに詳細なエラー原因（500エラーの中身）が出力されます。
        $this->withoutExceptionHandling();

        $user = User::factory()->create(['role' => 'user']);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => '09:00:00',
            'status' => '退勤済',
        ]);

        AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'note' => 'テスト申請',
        ]);

        $response = $this->actingAs($user)->get("/attendance/detail/{$attendance->id}");

        $response->assertStatus(200);
        $response->assertSee('承認待ちのため修正はできません。');
        $this->assertFalse($response->viewData('canEdit'));
    }

    /**
     * FN040：管理者による直接修正の成功
     * 修正が直接反映されるか
     */
    public function test_admin_can_update_attendance_directly()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '17:00:00',
            'status' => '退勤済',
            'note' => '修正前',
        ]);

        $updateData = [
            'clock_in' => '09:30',
            'clock_out' => '18:30',
            'note' => '管理者が修正しました',
            'breaks' => [
                ['start' => '12:00', 'end' => '13:00']
            ]
        ];

        $response = $this->actingAs($admin)->post("/attendance/detail/{$attendance->id}", $updateData);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => '09:30:00',
            'clock_out' => '18:30:00',
            'note' => '管理者が修正しました',
        ]);

        $this->assertDatabaseHas((new BreakTime)->getTable(), [
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);
    }

    /**
     * FN045：CSV出力機能（管理者）
     * 正常にストリームダウンロードできるか
     */
    public function test_admin_can_export_csv()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($admin)->get("/admin/attendance/staff/{$user->id}/export?month=" . Carbon::now()->format('Y-m'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="attendance_' . $user->name . '_' . Carbon::now()->format('Y-m') . '.csv"');
    }

    /**
     * FN029-1 / FN039-1：異常系(出退勤)
     * 退勤時間が出勤時間より前に設定された場合のエラー
     */
    public function test_validation_fails_if_clock_out_is_before_clock_in()
    {
        $user = User::factory()->create(['role' => 'user']);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        $invalidData = [
            'clock_in' => '10:00',
            'clock_out' => '09:00',
            'note' => 'テスト',
        ];

        $response = $this->actingAs($user)->post("/attendance/detail/{$attendance->id}", $invalidData);

        $response->assertSessionHasErrors(['clock_out' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    /**
     * FN029-2 / FN039-2：異常系(休憩)
     * 休憩開始時間が出勤時間より前に設定された場合のエラー
     */
    public function test_validation_fails_if_break_start_is_before_clock_in()
    {
        $user = User::factory()->create(['role' => 'user']);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => '10:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        $invalidData = [
            'clock_in' => '10:00',
            'clock_out' => '18:00',
            'note' => 'テスト',
            'breaks' => [
                ['start' => '09:00', 'end' => '10:30']
            ]
        ];

        $response = $this->actingAs($user)->post("/attendance/detail/{$attendance->id}", $invalidData);

        $response->assertSessionHasErrors(['breaks.0.start' => '休憩時間が不適切な値です']);
    }


}
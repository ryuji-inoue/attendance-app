<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. 会員登録後、認証メールが送信されることの確認
     */
    public function test_verification_email_is_sent_after_registration()
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'test@example.com')->first();

        // 登録したユーザーに対して、認証メールが送信されているか
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /**
     * 2. メール認証誘導画面で「認証はこちらから」ボタン（認証リンク）が有効であることの確認
     */
    public function test_can_access_email_verification_site()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // 署名付きの認証URLを作成
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $this->actingAs($user);
        $response = $this->get($verificationUrl);

        // 認証処理が受け付けられ、リダイレクトが発生するか
        $response->assertStatus(302);
    }

    /**
     * 3. メール認証を完了すると、勤怠登録画面に遷移することの確認
     */
    public function test_email_verification_redirects_to_attendance_screen()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $this->actingAs($user);
        $response = $this->get($verificationUrl);

        // パラメータを含めたリダイレクト先を指定
        $response->assertRedirect('/attendance?verified=1');

        // ユーザーが認証済みになっているかDB確認
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
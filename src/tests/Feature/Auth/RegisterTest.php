<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 名前が未入力の場合、バリデーションメッセージが表示されることの確認
     */
    public function test_name_is_required()
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['name' => 'お名前を入力してください']);
    }

    /**
     * メールアドレスが未入力の場合、バリデーションメッセージが表示されることの確認
     */
    public function test_email_is_required()
    {
        $response = $this->post('/register', [
            'name' => 'テスト会員',
            'email' => '',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /**
     * パスワードが8文字未満の場合、バリデーションメッセージが表示されることの確認
     */
    public function test_password_min_length()
    {
        $response = $this->post('/register', [
            'name' => 'テスト会員',
            'email' => 'test@example.com',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);
    }

    /**
     * パスワードが一致しない場合、バリデーションメッセージが表示されることの確認
     */
    public function test_password_confirmation_mismatch()
    {
        $response = $this->post('/register', [
            'name' => 'テスト会員',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password124',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードと一致しません']);
    }

    /**
     * 正常に入力がなされた場合、ユーザー情報が保存され、認証メールが送信されることの確認
     */
    public function test_registration_success_and_sends_verification_email()
    {
        Notification::fake();

        $userData = [
            'name' => 'テスト会員',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $response = $this->post('/register', $userData);

        // データベースに保存されているか
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'テスト会員',
        ]);

        // 認証メールが送信されたか
        $user = User::where('email', 'test@example.com')->first();
        Notification::assertSentTo($user, VerifyEmail::class);

        $response->assertRedirect('/attendance');
    }
}

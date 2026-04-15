<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // メール認証メールの日本語化
        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new MailMessage)
                ->subject('メールアドレスの確認')
                ->line('下のボタンをクリックして、メールアドレスを確認してください。')
                ->action('メールアドレスを確認する', $url)
                ->line('もしアカウント作成に心当たりがない場合は、このメールを破棄してください。');
        });

        Fortify::createUsersUsing(CreateNewUser::class);

        // ログインレスポンスのカスタマイズ（管理者・一般ユーザーの遷移先分岐）
        $this->app->singleton(
            \Laravel\Fortify\Contracts\LoginResponse::class,
            \App\Http\Responses\LoginResponse::class
        );

        // ログアウトレスポンスのカスタマイズ
        $this->app->singleton(
            \Laravel\Fortify\Contracts\LogoutResponse::class,
            \App\Http\Responses\LogoutResponse::class
        );

        // 会員登録画面のビュー指定
        Fortify::registerView(function () {
            return view('auth.register');
        });

        // ログイン画面のビュー指定
        Fortify::loginView(function () {
            return view('auth.login');
        });

        // メール承認誘導画面のビュー指定
        Fortify::verifyEmailView(function () {
            return view('auth.verify-email');
        });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;
            return Limit::perMinute(10)->by($email . $request->ip());
        });
    }
}

<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        // ログアウトフォームから渡されたリダイレクト先があればそこへ、なければ一般ログインへ
        $redirectPath = $request->input('logout_redirect', '/login');

        return redirect($redirectPath);
    }
}

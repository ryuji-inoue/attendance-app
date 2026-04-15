<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Support\Facades\Auth;

class LoginResponse implements LoginResponseContract
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $user = Auth::user();

        // 管理者の場合の遷移先
        if ($user && $user->role === 'admin') {
            return redirect('/admin/attendance/list');
        }

        // 一般ユーザーの場合の遷移先
        return redirect('/attendance');
    }
}

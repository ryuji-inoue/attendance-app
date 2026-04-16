<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance App - @yield('title')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&family=Noto+Sans+JP:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <div class="header__logo">
                @auth
                    <a href="/">
                        <img src="{{ asset('storage/images/logo.png') }}" alt="COACHTECH" class="header__logo-img">
                    </a>
                @else
                    <span>
                        <img src="{{ asset('storage/images/logo.png') }}" alt="COACHTECH" class="header__logo-img">
                    </span>
                @endauth
            </div>
            <nav class="header__nav">
                <ul class="header__nav-list">
                    @auth
                        {{-- 管理者にはメニューを表示 --}}
                        @if (Auth::user()->role === 'admin')
                            <li><a href="/admin/attendance/list" class="header__nav-link">勤怠一覧</a></li>
                            <li><a href="/admin/staff/list" class="header__nav-link">スタッフ一覧</a></li>
                            <li><a href="/stamp_correction_request/list" class="header__nav-link">申請一覧</a></li>

                        {{-- 一般ユーザーは、メール認証が完了している場合のみメニューを表示 --}}
                        @elseif (Auth::user()->hasVerifiedEmail())
                            <li><a href="/attendance" class="header__nav-link">勤怠</a></li>
                            <li><a href="/attendance/list" class="header__nav-link">勤怠一覧</a></li>
                            <li><a href="/stamp_correction_request/list" class="header__nav-link">申請</a></li>
                        @endif

                        {{-- ログアウトボタン --}}
                        <li>
                            <form action="/logout" method="POST">
                                @csrf
                                @if(Auth::user()->role === 'admin')
                                    <input type="hidden" name="logout_redirect" value="/admin/login">
                                @endif
                                <button type="submit" class="header__nav-link">ログアウト</button>
                            </form>
                        </li>
                    @endauth
                </ul>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="main__inner">
            @yield('content')
        </div>
    </main>
</body>

</html>
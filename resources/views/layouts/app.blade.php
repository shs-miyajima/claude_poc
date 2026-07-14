<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'アンケートシステム管理')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900">
    <nav class="bg-white border-b px-4 py-3 flex items-center justify-between">
        <div class="font-bold">アンケートシステム管理</div>
        @auth
            <div class="flex items-center gap-4 text-sm">
                @if (auth()->user()->isSuperUser())
                    <a href="{{ route('super.companies.index') }}" data-testid="nav-companies">企業一覧</a>
                @endif
                @if (auth()->user()->isAdmin() || session('acting_company_id'))
                    <a href="{{ route('company.home') }}" data-testid="nav-company-home">ホーム</a>
                    @if (auth()->user()->isSuperUser())
                        <a href="{{ route('company.admins.index') }}" data-testid="nav-admins">管理者一覧</a>
                    @endif
                    <a href="{{ route('company.users.index') }}" data-testid="nav-users">ユーザー一覧</a>
                    <a href="{{ route('company.departments.index') }}" data-testid="nav-departments">部署一覧</a>
                    <a href="{{ route('company.users.csv') }}" data-testid="nav-users-csv">CSV一括登録</a>
                    <a href="{{ route('company.surveys.index') }}" data-testid="nav-surveys">アンケート一覧</a>
                    @if (auth()->user()->isSuperUser())
                        <form method="POST" action="{{ route('super.switch.exit') }}">
                            @csrf
                            <button type="submit" data-testid="exit-company">全体画面へ戻る</button>
                        </form>
                    @endif
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" data-testid="logout-button">ログアウト</button>
                </form>
            </div>
        @endauth
    </nav>

    <main class="p-6">
        @if (session('status'))
            <div class="mb-4 rounded bg-green-100 text-green-800 px-4 py-2" data-testid="status-message">{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>
</body>
</html>

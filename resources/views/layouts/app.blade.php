<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'アンケートシステム管理')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-page-bg text-text-primary antialiased">
@auth
    @php
        $authUser = auth()->user();
        $isSuperUser = $authUser->isSuperUser();
        $isAdmin = $authUser->isAdmin();
        $isUser = $authUser->isUser();
        $inCompanyContext = $isAdmin || ($isSuperUser && session('acting_company_id') !== null);
        $companyName = app()->bound('currentCompany') ? app('currentCompany')->name : null;
        $roleLabel = match (true) {
            $isSuperUser => 'スーパーユーザー',
            $isAdmin => '管理者',
            default => 'ユーザー',
        };
        $departmentName = $authUser->department?->name;

        $navItemClass = fn (bool $active) => 'flex items-center rounded-[9px] px-3 py-[9px] text-[13.5px] mb-1 '
            .($active ? 'bg-accent-soft text-accent font-medium' : 'text-[#4b5563] hover:bg-subtle-bg-2');
    @endphp
    <div class="flex min-h-screen">
        <aside class="w-[248px] shrink-0 bg-card-bg border-r border-border-card flex flex-col">
            <div class="px-5 py-6 flex items-center gap-3">
                <div class="w-9 h-9 rounded-[10px] bg-gradient-to-br from-accent to-accent-light-to flex items-center justify-center text-white font-bold text-sm shrink-0">S</div>
                <div class="leading-tight min-w-0">
                    <div class="text-[13.5px] font-bold text-text-primary truncate">Survey Console</div>
                    <div class="text-[11.5px] text-text-muted truncate">社内アンケート基盤</div>
                </div>
            </div>

            <nav class="flex-1 px-3 overflow-auto">
                @if ($isSuperUser && ! $inCompanyContext)
                    <a href="{{ route('super.companies.index') }}" data-testid="nav-companies"
                       class="{{ $navItemClass(request()->routeIs('super.companies.*')) }}">
                        企業一覧
                    </a>
                @endif

                @if ($inCompanyContext)
                    @if ($companyName)
                        <div class="px-3 pt-2 pb-2 text-[11.5px] text-text-muted truncate">{{ $companyName }}</div>
                    @endif

                    <a href="{{ route('company.home') }}" data-testid="nav-company-home"
                       class="{{ $navItemClass(request()->routeIs('company.home')) }}">
                        ホーム
                    </a>
                    @if ($isSuperUser)
                        <a href="{{ route('company.admins.index') }}" data-testid="nav-admins"
                           class="{{ $navItemClass(request()->routeIs('company.admins.*')) }}">
                            管理者一覧
                        </a>
                    @endif
                    <a href="{{ route('company.surveys.index') }}" data-testid="nav-surveys"
                       class="{{ $navItemClass(request()->routeIs('company.surveys.*')) }}">
                        アンケート
                    </a>
                    <a href="{{ route('company.dashboard') }}" data-testid="nav-dashboard"
                       class="{{ $navItemClass(request()->routeIs('company.dashboard')) }}">
                        集計ダッシュボード
                    </a>

                    <div class="mt-4 mb-1 px-3 text-[11px] font-semibold text-text-faint tracking-wide">組織管理</div>
                    <a href="{{ route('company.users.index') }}" data-testid="nav-users"
                       class="{{ $navItemClass(request()->routeIs('company.users.*') && ! request()->routeIs('company.users.csv')) }}">
                        ユーザー
                    </a>
                    <a href="{{ route('company.departments.index') }}" data-testid="nav-departments"
                       class="{{ $navItemClass(request()->routeIs('company.departments.*')) }}">
                        部署マスタ
                    </a>
                    <a href="{{ route('company.users.csv') }}" data-testid="nav-users-csv"
                       class="{{ $navItemClass(request()->routeIs('company.users.csv')) }}">
                        CSV一括登録
                    </a>
                @endif

                @if ($isUser)
                    <a href="{{ route('user.home') }}"
                       class="{{ $navItemClass(request()->routeIs('user.home')) }}">
                        ホーム
                    </a>
                @endif
            </nav>

            <div class="border-t border-border-divider px-4 py-4">
                <div class="flex items-center gap-2 mb-3">
                    @if ($isSuperUser && $inCompanyContext)
                        {{-- PU-056-dsp: 代理操作中は作成者を特定できる情報(氏名・役割含む)を出さないため、個人名・役割ラベルを表示しない --}}
                        <div class="w-9 h-9 rounded-full bg-accent-soft text-accent-dark flex items-center justify-center font-semibold text-sm shrink-0">-</div>
                        <div class="leading-tight min-w-0">
                            <div class="text-[13px] font-medium text-text-primary truncate">代理操作中</div>
                        </div>
                    @else
                        <div class="w-9 h-9 rounded-full bg-accent-soft text-accent-dark flex items-center justify-center font-semibold text-sm shrink-0">
                            {{ mb_substr($authUser->name, 0, 1) }}
                        </div>
                        <div class="leading-tight min-w-0">
                            <div class="text-[13px] font-medium text-text-primary truncate">{{ $authUser->name }}</div>
                            <div class="text-[11.5px] text-text-muted truncate">{{ $roleLabel }}{{ $departmentName ? '・'.$departmentName : '' }}</div>
                        </div>
                    @endif
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" data-testid="logout-button" class="text-[12.5px] text-text-secondary hover:text-text-primary">ログアウト</button>
                </form>
            </div>
        </aside>

        <main class="flex-1 overflow-auto">
            <div class="px-8 py-[26px]">
                @if ($isSuperUser && $inCompanyContext)
                    <div class="mb-4 flex items-center justify-between rounded-[10px] border border-banner-border bg-banner-bg text-banner-text text-[13px] px-4 py-2" data-testid="acting-company-banner">
                        <span>代理操作中{{ $companyName ? '：'.$companyName : '' }}</span>
                        <form method="POST" action="{{ route('super.switch.exit') }}">
                            @csrf
                            <button type="submit" data-testid="exit-company" class="underline">全体画面へ戻る</button>
                        </form>
                    </div>
                @endif

                @if (session('status'))
                    <div class="mb-4 rounded-[10px] bg-success-bg text-success-text px-4 py-2 text-[13.5px]" data-testid="status-message">{{ session('status') }}</div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
@else
    <main class="min-h-screen flex flex-col items-center justify-center gap-4 px-4">
        @if (session('status'))
            <div class="rounded-[10px] bg-success-bg text-success-text px-4 py-2 text-[13.5px]" data-testid="status-message">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
@endauth
</body>
</html>

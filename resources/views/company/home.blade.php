@extends('layouts.app')

@section('title', '管理者ホーム')

@section('content')
<div data-testid="company-home">
    <h1 class="text-[22px] font-bold text-text-primary">ホーム</h1>
    <p class="text-[13.5px] text-text-secondary mt-1">{{ $company->name }}（{{ $company->code }}）のアンケート運用状況</p>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
        <a href="{{ route('company.surveys.index') }}" data-testid="home-surveys"
           class="rounded-[13px] border border-border-card bg-card-bg p-5 hover:bg-subtle-bg-2">
            <div class="text-[12.5px] text-text-secondary">公開中のアンケート</div>
            <div class="text-[26px] font-bold text-text-primary mt-1">{{ $publishedSurveyCount }}</div>
        </a>
        <div class="rounded-[13px] border border-border-card bg-card-bg p-5">
            <div class="text-[12.5px] text-text-secondary">下書き</div>
            <div class="text-[26px] font-bold text-text-primary mt-1">{{ $draftSurveyCount }}</div>
        </div>
        <div class="rounded-[13px] border border-border-card bg-card-bg p-5">
            <div class="text-[12.5px] text-text-secondary">対象ユーザー</div>
            <div class="text-[26px] font-bold text-text-primary mt-1">{{ $targetUserCount }}</div>
        </div>
        <div class="rounded-[13px] border border-border-card bg-card-bg p-5">
            <div class="text-[12.5px] text-text-secondary">平均回答率</div>
            <div class="text-[26px] font-bold text-accent mt-1">N/A</div>
            <div class="text-[11.5px] text-text-muted mt-0.5">回答機能は今後追加予定</div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <a href="{{ route('company.surveys.create') }}" data-testid="home-create-survey-link"
           class="rounded-[13px] bg-gradient-to-br from-accent to-accent-light-to p-5 text-white hover:brightness-105">
            <div class="text-[15px] font-bold">＋ 新しいアンケートを作成</div>
            <div class="text-[12.5px] text-white/80 mt-1">4つの設問形式・記名/匿名・回答期間を設定できます</div>
        </a>
        <a href="{{ route('company.dashboard') }}" data-testid="home-dashboard-link"
           class="rounded-[13px] border border-border-card bg-card-bg p-5 hover:bg-subtle-bg-2">
            <div class="text-[15px] font-bold text-text-secondary">集計ダッシュボードを見る</div>
            <div class="text-[12.5px] text-text-muted mt-1">属性で絞り込み、グラフで回答傾向を確認</div>
        </a>
    </div>

    <div class="rounded-[13px] border border-border-card bg-card-bg p-5 mt-4">
        <div class="text-[12.5px] font-semibold text-text-faint tracking-wide mb-2">組織管理</div>
        <ul class="flex flex-wrap gap-x-6 gap-y-2 text-[13.5px]">
            @if (auth()->user()->isSuperUser())
                <li><a href="{{ route('company.admins.index') }}" class="text-accent hover:text-accent-dark">管理者管理</a></li>
            @endif
            <li><a href="{{ route('company.users.index') }}" class="text-accent hover:text-accent-dark">ユーザー管理</a></li>
            <li><a href="{{ route('company.departments.index') }}" class="text-accent hover:text-accent-dark">部署マスタ管理</a></li>
            <li><a href="{{ route('company.users.csv') }}" class="text-accent hover:text-accent-dark">ユーザーCSV一括登録</a></li>
        </ul>
    </div>
</div>
@endsection

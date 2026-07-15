@extends('layouts.app')

@section('title', '企業一覧')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="flex justify-between items-start mb-6">
        <div>
            <h1 class="text-[22px] font-bold text-text-primary">企業一覧</h1>
            <p class="text-[13.5px] text-text-secondary mt-1">
                全{{ $companies->total() }}社。個別企業画面に切り替えて代理でアンケート作成・集計ができます。
            </p>
        </div>
        <a href="{{ route('super.companies.create') }}" data-testid="company-create-link"
           class="shrink-0 rounded-[9px] bg-accent px-4 py-2 text-[13.5px] font-medium text-white shadow-[0_1px_2px_rgba(75,83,224,.3)] hover:bg-accent-dark">
            ＋ 企業を登録
        </a>
    </div>

    <div class="rounded-[13px] border border-border-card bg-card-bg overflow-hidden" data-testid="company-list">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-border-divider">
                    <th class="px-5 py-3 text-[12.5px] font-medium text-text-secondary">企業コード</th>
                    <th class="px-5 py-3 text-[12.5px] font-medium text-text-secondary">企業名</th>
                    <th class="px-5 py-3 text-[12.5px] font-medium text-text-secondary">ユーザー数</th>
                    <th class="px-5 py-3 text-[12.5px] font-medium text-text-secondary">アンケート</th>
                    <th class="px-5 py-3 text-[12.5px] font-medium text-text-secondary">状態</th>
                    <th class="px-5 py-3 text-[12.5px] font-medium text-text-secondary">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($companies as $company)
                    <tr class="border-b border-border-divider last:border-b-0 hover:bg-subtle-bg-2" data-testid="company-row-{{ $company->id }}">
                        <td class="px-5 py-4 text-[13.5px] font-mono text-accent">{{ $company->code }}</td>
                        <td class="px-5 py-4 text-[13.5px] text-text-primary font-medium">{{ $company->name }}</td>
                        <td class="px-5 py-4 text-[13.5px] text-text-secondary">{{ $company->users_count }}名</td>
                        <td class="px-5 py-4 text-[13.5px] text-text-secondary">{{ $company->surveys_count }}件</td>
                        <td class="px-5 py-4">
                            <span data-testid="company-status-{{ $company->id }}"
                                  class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[12px] font-medium {{ $company->isActive() ? 'bg-success-bg text-success-text' : 'bg-disabled-bg text-disabled-text' }}">
                                @if ($company->isActive())
                                    <span class="w-1.5 h-1.5 rounded-full bg-success-dot"></span>
                                @endif
                                {{ $company->isActive() ? '有効' : '無効' }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3 text-[12.5px]">
                                @if ($company->isActive())
                                    <a href="{{ route('super.companies.edit', $company) }}" data-testid="company-edit-{{ $company->id }}"
                                       class="text-accent hover:text-accent-dark font-medium">編集</a>
                                    <form method="POST" action="{{ route('super.switch.enter', $company) }}">
                                        @csrf
                                        <button type="submit" data-testid="company-switch-{{ $company->id }}"
                                                class="text-accent hover:text-accent-dark font-medium">個別企業画面へ</button>
                                    </form>
                                    <form method="POST" action="{{ route('super.companies.deactivate', $company) }}" onsubmit="return confirm('無効化しますか？')">
                                        @csrf
                                        <button type="submit" data-testid="company-deactivate-{{ $company->id }}"
                                                class="text-danger hover:opacity-80 font-medium">無効化</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('super.companies.activate', $company) }}">
                                        @csrf
                                        <button type="submit" data-testid="company-activate-{{ $company->id }}"
                                                class="text-accent hover:text-accent-dark font-medium">有効化</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4" data-testid="company-pagination">{{ $companies->links() }}</div>
</div>
@endsection

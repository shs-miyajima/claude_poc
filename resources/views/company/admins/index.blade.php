@extends('layouts.app')

@section('title', '管理者一覧')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-start mb-6">
        <h1 class="text-[22px] font-bold text-text-primary">管理者一覧</h1>
        <a href="{{ route('company.admins.create') }}" data-testid="admin-create-link"
           class="shrink-0 rounded-[9px] bg-accent px-4 py-2 text-[13.5px] font-medium text-white shadow-[0_1px_2px_rgba(75,83,224,.3)] hover:bg-accent-dark">
            ＋ 管理者を登録
        </a>
    </div>

    <div class="rounded-[13px] border border-border-card bg-card-bg overflow-hidden" data-testid="admin-list">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-border-divider">
                    <th class="px-5 py-3 text-[12.5px] font-medium text-text-secondary">氏名</th>
                    <th class="px-5 py-3 text-[12.5px] font-medium text-text-secondary">メールアドレス</th>
                    <th class="px-5 py-3 text-[12.5px] font-medium text-text-secondary">状態</th>
                    <th class="px-5 py-3 text-[12.5px] font-medium text-text-secondary">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($admins as $admin)
                    <tr class="border-b border-border-divider last:border-b-0 hover:bg-subtle-bg-2" data-testid="admin-row-{{ $admin->id }}">
                        <td class="px-5 py-4 text-[13.5px] text-text-primary font-medium">{{ $admin->name }}</td>
                        <td class="px-5 py-4 text-[13.5px] font-mono text-text-secondary">{{ $admin->email }}</td>
                        <td class="px-5 py-4">
                            <span data-testid="admin-status-{{ $admin->id }}"
                                  class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[12px] font-medium {{ $admin->isActive() ? 'bg-success-bg text-success-text' : 'bg-disabled-bg text-disabled-text' }}">
                                @if ($admin->isActive())
                                    <span class="w-1.5 h-1.5 rounded-full bg-success-dot"></span>
                                @endif
                                {{ $admin->isActive() ? '有効' : '無効' }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3 text-[12.5px]">
                                @if ($admin->isActive())
                                    <a href="{{ route('company.admins.edit', $admin) }}" data-testid="admin-edit-{{ $admin->id }}"
                                       class="text-accent hover:text-accent-dark font-medium">編集</a>
                                    <form method="POST" action="{{ route('company.admins.deactivate', $admin) }}" onsubmit="return confirm('無効化しますか？')">
                                        @csrf
                                        <button type="submit" data-testid="admin-deactivate-{{ $admin->id }}"
                                                class="text-danger hover:opacity-80 font-medium">無効化</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('company.admins.activate', $admin) }}">
                                        @csrf
                                        <button type="submit" data-testid="admin-activate-{{ $admin->id }}"
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

    <div class="mt-4" data-testid="admin-pagination">{{ $admins->links() }}</div>
</div>
@endsection

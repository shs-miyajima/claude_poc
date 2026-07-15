@extends('layouts.app')

@section('title', 'ユーザー一覧')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-start mb-6">
        <h1 class="text-[22px] font-bold text-text-primary">ユーザー一覧</h1>
        <a href="{{ route('company.users.create') }}" data-testid="user-create-link"
           class="shrink-0 rounded-[9px] bg-accent px-4 py-2 text-[13.5px] font-medium text-white shadow-[0_1px_2px_rgba(75,83,224,.3)] hover:bg-accent-dark">
            ＋ ユーザーを登録
        </a>
    </div>

    <div class="rounded-[13px] border border-border-card bg-card-bg overflow-hidden" data-testid="user-list">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-border-divider">
                    <th class="px-5 py-3 text-[12.5px] font-medium text-text-secondary">氏名</th>
                    <th class="px-5 py-3 text-[12.5px] font-medium text-text-secondary">メールアドレス</th>
                    <th class="px-5 py-3 text-[12.5px] font-medium text-text-secondary">部署</th>
                    <th class="px-5 py-3 text-[12.5px] font-medium text-text-secondary">状態</th>
                    <th class="px-5 py-3 text-[12.5px] font-medium text-text-secondary">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr class="border-b border-border-divider last:border-b-0 hover:bg-subtle-bg-2" data-testid="user-row-{{ $user->id }}">
                        <td class="px-5 py-4 text-[13.5px] text-text-primary font-medium">{{ $user->name }}</td>
                        <td class="px-5 py-4 text-[13.5px] font-mono text-text-secondary">{{ $user->email }}</td>
                        <td class="px-5 py-4 text-[13.5px] text-text-secondary">{{ $user->department?->name }}</td>
                        <td class="px-5 py-4">
                            <span data-testid="user-status-{{ $user->id }}"
                                  class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[12px] font-medium {{ $user->isActive() ? 'bg-success-bg text-success-text' : 'bg-disabled-bg text-disabled-text' }}">
                                @if ($user->isActive())
                                    <span class="w-1.5 h-1.5 rounded-full bg-success-dot"></span>
                                @endif
                                {{ $user->isActive() ? '有効' : '無効' }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3 text-[12.5px]">
                                @if ($user->isActive())
                                    <a href="{{ route('company.users.edit', $user) }}" data-testid="user-edit-{{ $user->id }}"
                                       class="text-accent hover:text-accent-dark font-medium">編集</a>
                                    <form method="POST" action="{{ route('company.users.deactivate', $user) }}" onsubmit="return confirm('無効化しますか？')">
                                        @csrf
                                        <button type="submit" data-testid="user-deactivate-{{ $user->id }}"
                                                class="text-danger hover:opacity-80 font-medium">無効化</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('company.users.activate', $user) }}">
                                        @csrf
                                        <button type="submit" data-testid="user-activate-{{ $user->id }}"
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

    <div class="mt-4" data-testid="user-pagination">{{ $users->links() }}</div>
</div>
@endsection

@extends('layouts.app')

@section('title', '部署一覧')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-start mb-6">
        <h1 class="text-[22px] font-bold text-text-primary">部署マスタ</h1>
        <a href="{{ route('company.departments.create') }}" data-testid="department-create-link"
           class="shrink-0 rounded-[9px] bg-accent px-4 py-2 text-[13.5px] font-medium text-white shadow-[0_1px_2px_rgba(75,83,224,.3)] hover:bg-accent-dark">
            ＋ 部署を登録
        </a>
    </div>

    <div class="rounded-[13px] border border-border-card bg-card-bg overflow-hidden" data-testid="department-list">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-border-divider">
                    <th class="px-5 py-3 text-[12.5px] font-medium text-text-secondary">部署名</th>
                    <th class="px-5 py-3 text-[12.5px] font-medium text-text-secondary">状態</th>
                    <th class="px-5 py-3 text-[12.5px] font-medium text-text-secondary">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($departments as $department)
                    <tr class="border-b border-border-divider last:border-b-0 hover:bg-subtle-bg-2" data-testid="department-row-{{ $department->id }}">
                        <td class="px-5 py-4 text-[13.5px] text-text-primary font-medium">{{ $department->name }}</td>
                        <td class="px-5 py-4">
                            <span data-testid="department-status-{{ $department->id }}"
                                  class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[12px] font-medium {{ $department->isActive() ? 'bg-success-bg text-success-text' : 'bg-disabled-bg text-disabled-text' }}">
                                @if ($department->isActive())
                                    <span class="w-1.5 h-1.5 rounded-full bg-success-dot"></span>
                                @endif
                                {{ $department->isActive() ? '有効' : '無効' }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3 text-[12.5px]">
                                @if ($department->isActive())
                                    <a href="{{ route('company.departments.edit', $department) }}" data-testid="department-edit-{{ $department->id }}"
                                       class="text-accent hover:text-accent-dark font-medium">編集</a>
                                    <form method="POST" action="{{ route('company.departments.deactivate', $department) }}" onsubmit="return confirm('無効化しますか？')">
                                        @csrf
                                        <button type="submit" data-testid="department-deactivate-{{ $department->id }}"
                                                class="text-danger hover:opacity-80 font-medium">無効化</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('company.departments.activate', $department) }}">
                                        @csrf
                                        <button type="submit" data-testid="department-activate-{{ $department->id }}"
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

    <div class="mt-4" data-testid="department-pagination">{{ $departments->links() }}</div>
</div>
@endsection

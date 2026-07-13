@extends('layouts.app')

@section('title', '管理者一覧')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-lg font-bold">管理者一覧</h1>
        <a href="{{ route('company.admins.create') }}" data-testid="admin-create-link" class="bg-blue-600 text-white px-3 py-1 rounded">管理者登録</a>
    </div>

    <table class="w-full bg-white rounded shadow" data-testid="admin-list">
        <thead>
            <tr class="text-left border-b">
                <th class="p-2">氏名</th>
                <th class="p-2">メールアドレス</th>
                <th class="p-2">状態</th>
                <th class="p-2">操作</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($admins as $admin)
                <tr class="border-b" data-testid="admin-row-{{ $admin->id }}">
                    <td class="p-2">{{ $admin->name }}</td>
                    <td class="p-2">{{ $admin->email }}</td>
                    <td class="p-2"><span data-testid="admin-status-{{ $admin->id }}">{{ $admin->isActive() ? '有効' : '無効' }}</span></td>
                    <td class="p-2">
                        <div class="flex gap-2">
                            @if ($admin->isActive())
                                <a href="{{ route('company.admins.edit', $admin) }}" data-testid="admin-edit-{{ $admin->id }}">編集</a>
                                <form method="POST" action="{{ route('company.admins.deactivate', $admin) }}" onsubmit="return confirm('無効化しますか？')">
                                    @csrf
                                    <button type="submit" data-testid="admin-deactivate-{{ $admin->id }}">無効化</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('company.admins.activate', $admin) }}">
                                    @csrf
                                    <button type="submit" data-testid="admin-activate-{{ $admin->id }}">有効化</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4" data-testid="admin-pagination">{{ $admins->links() }}</div>
</div>
@endsection

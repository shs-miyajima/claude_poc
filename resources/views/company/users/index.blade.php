@extends('layouts.app')

@section('title', 'ユーザー一覧')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-lg font-bold">ユーザー一覧</h1>
        <a href="{{ route('company.users.create') }}" data-testid="user-create-link" class="bg-blue-600 text-white px-3 py-1 rounded">ユーザー登録</a>
    </div>

    <table class="w-full bg-white rounded shadow" data-testid="user-list">
        <thead>
            <tr class="text-left border-b">
                <th class="p-2">氏名</th>
                <th class="p-2">メールアドレス</th>
                <th class="p-2">部署</th>
                <th class="p-2">状態</th>
                <th class="p-2">操作</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr class="border-b" data-testid="user-row-{{ $user->id }}">
                    <td class="p-2">{{ $user->name }}</td>
                    <td class="p-2">{{ $user->email }}</td>
                    <td class="p-2">{{ $user->department?->name }}</td>
                    <td class="p-2"><span data-testid="user-status-{{ $user->id }}">{{ $user->isActive() ? '有効' : '無効' }}</span></td>
                    <td class="p-2">
                        <div class="flex gap-2">
                            @if ($user->isActive())
                                <a href="{{ route('company.users.edit', $user) }}" data-testid="user-edit-{{ $user->id }}">編集</a>
                                <form method="POST" action="{{ route('company.users.deactivate', $user) }}" onsubmit="return confirm('無効化しますか？')">
                                    @csrf
                                    <button type="submit" data-testid="user-deactivate-{{ $user->id }}">無効化</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('company.users.activate', $user) }}">
                                    @csrf
                                    <button type="submit" data-testid="user-activate-{{ $user->id }}">有効化</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4" data-testid="user-pagination">{{ $users->links() }}</div>
</div>
@endsection

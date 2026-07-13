@extends('layouts.app')

@section('title', '部署一覧')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-lg font-bold">部署一覧</h1>
        <a href="{{ route('company.departments.create') }}" data-testid="department-create-link" class="bg-blue-600 text-white px-3 py-1 rounded">部署登録</a>
    </div>

    <table class="w-full bg-white rounded shadow" data-testid="department-list">
        <thead>
            <tr class="text-left border-b">
                <th class="p-2">部署名</th>
                <th class="p-2">状態</th>
                <th class="p-2">操作</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($departments as $department)
                <tr class="border-b" data-testid="department-row-{{ $department->id }}">
                    <td class="p-2">{{ $department->name }}</td>
                    <td class="p-2"><span data-testid="department-status-{{ $department->id }}">{{ $department->isActive() ? '有効' : '無効' }}</span></td>
                    <td class="p-2">
                        <div class="flex gap-2">
                            @if ($department->isActive())
                                <a href="{{ route('company.departments.edit', $department) }}" data-testid="department-edit-{{ $department->id }}">編集</a>
                                <form method="POST" action="{{ route('company.departments.deactivate', $department) }}" onsubmit="return confirm('無効化しますか？')">
                                    @csrf
                                    <button type="submit" data-testid="department-deactivate-{{ $department->id }}">無効化</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('company.departments.activate', $department) }}">
                                    @csrf
                                    <button type="submit" data-testid="department-activate-{{ $department->id }}">有効化</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4" data-testid="department-pagination">{{ $departments->links() }}</div>
</div>
@endsection

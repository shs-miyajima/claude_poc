@extends('layouts.app')

@section('title', '企業一覧')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-lg font-bold">企業一覧</h1>
        <a href="{{ route('super.companies.create') }}" data-testid="company-create-link" class="bg-blue-600 text-white px-3 py-1 rounded">企業登録</a>
    </div>

    <table class="w-full bg-white rounded shadow" data-testid="company-list">
        <thead>
            <tr class="text-left border-b">
                <th class="p-2">企業コード</th>
                <th class="p-2">企業名</th>
                <th class="p-2">状態</th>
                <th class="p-2">操作</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($companies as $company)
                <tr class="border-b" data-testid="company-row-{{ $company->id }}">
                    <td class="p-2">{{ $company->code }}</td>
                    <td class="p-2">{{ $company->name }}</td>
                    <td class="p-2">
                        <span data-testid="company-status-{{ $company->id }}">{{ $company->isActive() ? '有効' : '無効' }}</span>
                    </td>
                    <td class="p-2">
                        <div class="flex gap-2">
                            @if ($company->isActive())
                                <a href="{{ route('super.companies.edit', $company) }}" data-testid="company-edit-{{ $company->id }}">編集</a>
                                <form method="POST" action="{{ route('super.switch.enter', $company) }}">
                                    @csrf
                                    <button type="submit" data-testid="company-switch-{{ $company->id }}">個別企業画面へ</button>
                                </form>
                                <form method="POST" action="{{ route('super.companies.deactivate', $company) }}" onsubmit="return confirm('無効化しますか？')">
                                    @csrf
                                    <button type="submit" data-testid="company-deactivate-{{ $company->id }}">無効化</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('super.companies.activate', $company) }}">
                                    @csrf
                                    <button type="submit" data-testid="company-activate-{{ $company->id }}">有効化</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4" data-testid="company-pagination">
        {{ $companies->links() }}
    </div>
</div>
@endsection

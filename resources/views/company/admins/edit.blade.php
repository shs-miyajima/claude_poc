@extends('layouts.app')

@section('title', '管理者編集')

@section('content')
<div class="max-w-sm mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-lg font-bold mb-4">管理者編集</h1>

    <form method="POST" action="{{ route('company.admins.update', $admin) }}">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="block text-sm mb-1">氏名</label>
            <input type="text" name="name" value="{{ old('name', $admin->name) }}" data-testid="name-input" class="border rounded w-full px-2 py-1">
            @error('name')<div class="text-red-600 text-sm" data-testid="name-error">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="block text-sm mb-1">メールアドレス</label>
            <input type="text" name="email" value="{{ old('email', $admin->email) }}" data-testid="email-input" class="border rounded w-full px-2 py-1">
            @error('email')<div class="text-red-600 text-sm" data-testid="email-error">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="block text-sm mb-1">初期パスワード（空欄なら変更しません）</label>
            <input type="password" name="password" data-testid="password-input" class="border rounded w-full px-2 py-1">
            @error('password')<div class="text-red-600 text-sm" data-testid="password-error">{{ $message }}</div>@enderror
        </div>
        <button type="submit" data-testid="admin-submit" class="bg-blue-600 text-white px-4 py-2 rounded w-full">更新</button>
    </form>
</div>
@endsection

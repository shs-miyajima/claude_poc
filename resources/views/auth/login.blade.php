@extends('layouts.app')

@section('title', 'ログイン')

@section('content')
<div class="max-w-sm mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-lg font-bold mb-4">ログイン</h1>

    @if ($errors->any())
        <div class="mb-4 text-red-600 text-sm" data-testid="login-error">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.attempt') }}">
        @csrf
        <div class="mb-3">
            <label class="block text-sm mb-1">企業コード</label>
            <input type="text" name="company_code" value="{{ old('company_code') }}" data-testid="company-code-input" class="border rounded w-full px-2 py-1">
        </div>
        <div class="mb-3">
            <label class="block text-sm mb-1">メールアドレス</label>
            <input type="text" name="email" value="{{ old('email') }}" data-testid="email-input" class="border rounded w-full px-2 py-1">
        </div>
        <div class="mb-3">
            <label class="block text-sm mb-1">パスワード</label>
            <input type="password" name="password" data-testid="password-input" class="border rounded w-full px-2 py-1">
        </div>
        <button type="submit" data-testid="login-submit" class="bg-blue-600 text-white px-4 py-2 rounded w-full">ログイン</button>
    </form>
</div>
@endsection

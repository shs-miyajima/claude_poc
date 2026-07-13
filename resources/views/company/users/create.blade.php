@extends('layouts.app')

@section('title', 'ユーザー登録')

@section('content')
<div class="max-w-sm mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-lg font-bold mb-4">ユーザー登録</h1>

    <form method="POST" action="{{ route('company.users.store') }}">
        @csrf
        <div class="mb-3">
            <label class="block text-sm mb-1">氏名</label>
            <input type="text" name="name" value="{{ old('name') }}" data-testid="name-input" class="border rounded w-full px-2 py-1">
            @error('name')<div class="text-red-600 text-sm" data-testid="name-error">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="block text-sm mb-1">メールアドレス</label>
            <input type="text" name="email" value="{{ old('email') }}" data-testid="email-input" class="border rounded w-full px-2 py-1">
            @error('email')<div class="text-red-600 text-sm" data-testid="email-error">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="block text-sm mb-1">初期パスワード</label>
            <input type="password" name="password" data-testid="password-input" class="border rounded w-full px-2 py-1">
            @error('password')<div class="text-red-600 text-sm" data-testid="password-error">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="block text-sm mb-1">生年月日</label>
            <input type="text" name="birth_date" placeholder="YYYY-MM-DD" value="{{ old('birth_date') }}" data-testid="birth-date-input" class="border rounded w-full px-2 py-1">
            @error('birth_date')<div class="text-red-600 text-sm" data-testid="birth-date-error">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="block text-sm mb-1">入社年月日</label>
            <input type="text" name="hire_date" placeholder="YYYY-MM-DD" value="{{ old('hire_date') }}" data-testid="hire-date-input" class="border rounded w-full px-2 py-1">
            @error('hire_date')<div class="text-red-600 text-sm" data-testid="hire-date-error">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="block text-sm mb-1">性別</label>
            <select name="gender" data-testid="gender-input" class="border rounded w-full px-2 py-1">
                <option value="">選択してください</option>
                @foreach (\App\Enums\Gender::cases() as $gender)
                    <option value="{{ $gender->value }}" @selected(old('gender') === $gender->value)>{{ $gender->label() }}</option>
                @endforeach
            </select>
            @error('gender')<div class="text-red-600 text-sm" data-testid="gender-error">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="block text-sm mb-1">部署</label>
            <select name="department_id" data-testid="department-input" class="border rounded w-full px-2 py-1">
                <option value="">選択してください</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
            @error('department_id')<div class="text-red-600 text-sm" data-testid="department-error">{{ $message }}</div>@enderror
        </div>
        <button type="submit" data-testid="user-submit" class="bg-blue-600 text-white px-4 py-2 rounded w-full">登録</button>
    </form>
</div>
@endsection

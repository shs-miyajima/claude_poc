@extends('layouts.app')

@section('title', 'ユーザー編集')

@section('content')
<div class="max-w-md mx-auto">
    <h1 class="text-[22px] font-bold text-text-primary mb-6">ユーザー編集</h1>

    <div class="rounded-[13px] border border-border-card bg-card-bg p-6">
        <form method="POST" action="{{ route('company.users.update', $user) }}">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block text-[13px] font-medium text-text-secondary mb-1">氏名</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" data-testid="name-input"
                       class="border border-border-input rounded-[8px] w-full px-3 py-2 text-[13.5px] text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-soft-border focus:border-accent">
                @error('name')<div class="text-danger text-[12.5px] mt-1" data-testid="name-error">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label class="block text-[13px] font-medium text-text-secondary mb-1">メールアドレス</label>
                <input type="text" name="email" value="{{ old('email', $user->email) }}" data-testid="email-input"
                       class="border border-border-input rounded-[8px] w-full px-3 py-2 text-[13.5px] text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-soft-border focus:border-accent">
                @error('email')<div class="text-danger text-[12.5px] mt-1" data-testid="email-error">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label class="block text-[13px] font-medium text-text-secondary mb-1">初期パスワード（空欄なら変更しません）</label>
                <input type="password" name="password" data-testid="password-input"
                       class="border border-border-input rounded-[8px] w-full px-3 py-2 text-[13.5px] text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-soft-border focus:border-accent">
                @error('password')<div class="text-danger text-[12.5px] mt-1" data-testid="password-error">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label class="block text-[13px] font-medium text-text-secondary mb-1">生年月日</label>
                <input type="text" name="birth_date" placeholder="YYYY-MM-DD" value="{{ old('birth_date', optional($user->birth_date)->format('Y-m-d')) }}" data-testid="birth-date-input"
                       class="border border-border-input rounded-[8px] w-full px-3 py-2 text-[13.5px] text-text-primary placeholder:text-text-placeholder focus:outline-none focus:ring-2 focus:ring-accent-soft-border focus:border-accent">
                @error('birth_date')<div class="text-danger text-[12.5px] mt-1" data-testid="birth-date-error">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label class="block text-[13px] font-medium text-text-secondary mb-1">入社年月日</label>
                <input type="text" name="hire_date" placeholder="YYYY-MM-DD" value="{{ old('hire_date', optional($user->hire_date)->format('Y-m-d')) }}" data-testid="hire-date-input"
                       class="border border-border-input rounded-[8px] w-full px-3 py-2 text-[13.5px] text-text-primary placeholder:text-text-placeholder focus:outline-none focus:ring-2 focus:ring-accent-soft-border focus:border-accent">
                @error('hire_date')<div class="text-danger text-[12.5px] mt-1" data-testid="hire-date-error">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label class="block text-[13px] font-medium text-text-secondary mb-1">性別</label>
                <select name="gender" data-testid="gender-input"
                        class="border border-border-input rounded-[8px] w-full px-3 py-2 text-[13.5px] text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-soft-border focus:border-accent">
                    <option value="">選択してください</option>
                    @foreach (\App\Enums\Gender::cases() as $gender)
                        <option value="{{ $gender->value }}" @selected(old('gender', $user->gender?->value) === $gender->value)>{{ $gender->label() }}</option>
                    @endforeach
                </select>
                @error('gender')<div class="text-danger text-[12.5px] mt-1" data-testid="gender-error">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label class="block text-[13px] font-medium text-text-secondary mb-1">部署</label>
                <select name="department_id" data-testid="department-input"
                        class="border border-border-input rounded-[8px] w-full px-3 py-2 text-[13.5px] text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-soft-border focus:border-accent">
                    <option value="">選択してください</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) old('department_id', $user->department_id) === (string) $department->id)>{{ $department->name }}@if (! $department->isActive())（無効）@endif</option>
                    @endforeach
                </select>
                @error('department_id')<div class="text-danger text-[12.5px] mt-1" data-testid="department-error">{{ $message }}</div>@enderror
            </div>
            <button type="submit" data-testid="user-submit"
                    class="w-full rounded-[9px] bg-accent px-4 py-2 text-[13.5px] font-medium text-white shadow-[0_1px_2px_rgba(75,83,224,.3)] hover:bg-accent-dark">
                更新
            </button>
        </form>
    </div>
</div>
@endsection

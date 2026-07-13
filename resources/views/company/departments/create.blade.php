@extends('layouts.app')

@section('title', '部署登録')

@section('content')
<div class="max-w-sm mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-lg font-bold mb-4">部署登録</h1>

    <form method="POST" action="{{ route('company.departments.store') }}">
        @csrf
        <div class="mb-3">
            <label class="block text-sm mb-1">部署名</label>
            <input type="text" name="name" value="{{ old('name') }}" data-testid="department-name-input" class="border rounded w-full px-2 py-1">
            @error('name')<div class="text-red-600 text-sm" data-testid="name-error">{{ $message }}</div>@enderror
        </div>
        <button type="submit" data-testid="department-submit" class="bg-blue-600 text-white px-4 py-2 rounded w-full">登録</button>
    </form>
</div>
@endsection

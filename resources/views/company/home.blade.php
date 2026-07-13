@extends('layouts.app')

@section('title', '管理者ホーム')

@section('content')
<div data-testid="company-home">
    <h1 class="text-lg font-bold mb-4">{{ $company->name }}（{{ $company->code }}）管理画面</h1>
    <ul class="list-disc list-inside">
        @if (auth()->user()->isSuperUser())
            <li><a href="{{ route('company.admins.index') }}">管理者管理</a></li>
        @endif
        <li><a href="{{ route('company.users.index') }}">ユーザー管理</a></li>
        <li><a href="{{ route('company.departments.index') }}">部署マスタ管理</a></li>
        <li><a href="{{ route('company.users.csv') }}">ユーザーCSV一括登録</a></li>
    </ul>
</div>
@endsection

@extends('layouts.app')

@section('title', 'ユーザーホーム')

@section('content')
<div data-testid="user-home">
    <h1 class="text-lg font-bold">ようこそ、{{ auth()->user()->name }} さん</h1>
    <p class="text-sm text-gray-600 mt-2">アンケート機能は今後追加されます。</p>
</div>
@endsection

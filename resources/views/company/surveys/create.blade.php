@extends('layouts.app')

@section('title', 'アンケート作成')

@section('content')
<div class="max-w-3xl mx-auto">
    <h1 class="text-lg font-bold mb-4">アンケート作成</h1>

    @include('company.surveys._form', ['survey' => null])
</div>
@endsection

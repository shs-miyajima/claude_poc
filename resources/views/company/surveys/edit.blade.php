@extends('layouts.app')

@section('title', 'アンケート編集')

@section('content')
<div class="max-w-3xl mx-auto">
    <h1 class="text-lg font-bold mb-4">アンケート編集</h1>

    @include('company.surveys._form', ['survey' => $survey])
</div>
@endsection

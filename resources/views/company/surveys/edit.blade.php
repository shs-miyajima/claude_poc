@extends('layouts.app')

@section('title', 'アンケート編集')

@section('content')
<div class="max-w-[760px] mx-auto">
    <h1 class="text-[22px] font-bold text-text-primary mb-6">アンケート編集</h1>

    @include('company.surveys._form', ['survey' => $survey])
</div>
@endsection

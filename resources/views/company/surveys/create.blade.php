@extends('layouts.app')

@section('title', 'アンケート作成')

@section('content')
<div class="max-w-[760px] mx-auto">
    <h1 class="text-[22px] font-bold text-text-primary mb-6">アンケート作成</h1>

    @include('company.surveys._form', ['survey' => null])
</div>
@endsection

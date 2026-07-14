@extends('layouts.app')

@section('title', 'アンケート一覧')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-lg font-bold">アンケート一覧</h1>
        <a href="{{ route('company.surveys.create') }}" data-testid="survey-create-link" class="bg-blue-600 text-white px-3 py-1 rounded">新規作成</a>
    </div>

    <table class="w-full bg-white rounded shadow" data-testid="survey-list">
        <thead>
            <tr class="text-left border-b">
                <th class="p-2">タイトル</th>
                <th class="p-2">状態</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($surveys as $survey)
                <tr class="border-b" data-testid="survey-row-{{ $survey->id }}">
                    <td class="p-2"><a href="{{ route('company.surveys.show', $survey) }}" data-testid="survey-show-link-{{ $survey->id }}">{{ $survey->title }}</a></td>
                    <td class="p-2"><span data-testid="survey-status-{{ $survey->id }}">{{ $survey->status->label() }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4" data-testid="survey-pagination">{{ $surveys->links() }}</div>
</div>
@endsection

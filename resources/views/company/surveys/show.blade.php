@extends('layouts.app')

@section('title', 'アンケート詳細')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-lg font-bold" data-testid="survey-title">{{ $survey->title }}</h1>
        <span data-testid="survey-status">{{ $survey->status->label() }}</span>
    </div>

    <div class="mb-4 text-sm">
        <div>回答期間: {{ $survey->answer_start_date->format('Y-m-d') }} 〜 {{ $survey->answer_end_date->format('Y-m-d') }}</div>
        <div>{{ $survey->answer_visibility->label() }}</div>
    </div>

    @if ($survey->isDraft())
        <div class="mb-4 flex gap-2">
            <a href="{{ route('company.surveys.edit', $survey) }}" data-testid="survey-edit-link" class="px-3 py-1 border rounded">編集</a>
            <form method="POST" action="{{ route('company.surveys.publish', $survey) }}">
                @csrf
                <button type="submit" data-testid="survey-publish-button" class="px-3 py-1 border rounded">公開</button>
            </form>
            <form method="POST" action="{{ route('company.surveys.destroy', $survey) }}" onsubmit="return confirm('削除しますか？')">
                @csrf
                @method('DELETE')
                <button type="submit" data-testid="survey-delete-button" class="px-3 py-1 border rounded text-red-600">削除</button>
            </form>
        </div>
    @endif

    <div>
        @foreach ($survey->questions as $question)
            <div class="border rounded p-4 mb-3 bg-white" data-testid="survey-question">
                <div class="font-bold" data-testid="survey-question-body">{{ $question->body }}</div>
                <div class="text-sm" data-testid="survey-question-type">{{ $question->question_type->label() }}</div>
                <div class="text-sm" data-testid="survey-question-required">{{ $question->is_required ? '必須' : '任意' }}</div>

                @if ($question->hasChoices())
                    <ul class="list-disc list-inside mt-2">
                        @foreach ($question->choices as $choice)
                            <li data-testid="survey-choice">{{ $choice->body }}</li>
                        @endforeach
                    </ul>
                @endif

                @if ($question->question_type->value === 'scale')
                    <div class="mt-2 flex items-center gap-2 text-sm" data-testid="survey-scale-display">
                        @if ($question->scale_min_label)
                            <span data-testid="survey-scale-min-label">{{ $question->scale_min_label }}</span>
                        @endif
                        <span>1〜5</span>
                        @if ($question->scale_max_label)
                            <span data-testid="survey-scale-max-label">{{ $question->scale_max_label }}</span>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection

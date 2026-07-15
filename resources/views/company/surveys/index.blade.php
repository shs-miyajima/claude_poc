@extends('layouts.app')

@section('title', 'アンケート一覧')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-start mb-6">
        <div>
            <h1 class="text-[22px] font-bold text-text-primary">アンケート</h1>
            <p class="text-[13.5px] text-text-secondary mt-1">作成・公開したアンケートの一覧</p>
        </div>
        <a href="{{ route('company.surveys.create') }}" data-testid="survey-create-link"
           class="shrink-0 rounded-[9px] bg-accent px-4 py-2 text-[13.5px] font-medium text-white shadow-[0_1px_2px_rgba(75,83,224,.3)] hover:bg-accent-dark">
            ＋ アンケートを作成
        </a>
    </div>

    <div class="rounded-[13px] border border-border-card bg-card-bg overflow-hidden" data-testid="survey-list">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-border-divider">
                    <th class="px-5 py-3 text-[12.5px] font-medium text-text-secondary">タイトル</th>
                    <th class="px-5 py-3 text-[12.5px] font-medium text-text-secondary">状態</th>
                    <th class="px-5 py-3 text-[12.5px] font-medium text-text-secondary">回答期間・設問数</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($surveys as $survey)
                    <tr class="border-b border-border-divider last:border-b-0 hover:bg-subtle-bg-2" data-testid="survey-row-{{ $survey->id }}">
                        <td class="px-5 py-4">
                            <a href="{{ route('company.surveys.show', $survey) }}" data-testid="survey-show-link-{{ $survey->id }}"
                               class="text-[13.5px] font-medium text-text-primary hover:text-accent">
                                {{ $survey->title }}
                            </a>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-1.5">
                                <span data-testid="survey-status-{{ $survey->id }}"
                                      class="inline-flex items-center rounded-full px-2.5 py-1 text-[12px] font-medium {{ $survey->isPublished() ? 'bg-success-bg text-success-text' : 'bg-warning-bg text-warning-text' }}">
                                    {{ $survey->isPublished() ? '公開中' : '下書き' }}
                                </span>
                                @if ($survey->answer_visibility->value === 'anonymous')
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[12px] font-medium bg-accent-soft text-accent">匿名</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-4 text-[12.5px] text-text-muted">
                            {{ $survey->answer_start_date->format('Y-m-d') }} 〜 {{ $survey->answer_end_date->format('Y-m-d') }}
                            ・設問{{ $survey->questions_count }}問
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4" data-testid="survey-pagination">{{ $surveys->links() }}</div>
</div>
@endsection

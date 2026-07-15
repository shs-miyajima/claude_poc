@extends('layouts.app')

@section('title', 'アンケート')

@section('content')
<div data-testid="user-home">
    <h1 class="text-[22px] font-bold text-text-primary">アンケート</h1>
    <p class="text-[13.5px] text-text-secondary mt-1">あなたに配布されたアンケートです。回答期間内は提出後も修正できます。</p>

    <div class="flex flex-col gap-3 mt-6" data-testid="user-survey-list">
        @forelse ($surveyItems as $item)
            @php $survey = $item['survey']; @endphp
            <div class="rounded-[13px] border border-border-card bg-card-bg p-5 flex items-center justify-between gap-4" data-testid="user-survey-row-{{ $survey->id }}">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[12px] font-medium {{ $item['isExpired'] ? 'bg-disabled-bg text-disabled-text' : 'bg-warning-bg text-warning-text' }}">
                            {{ $item['isExpired'] ? '期限切れ' : '未回答' }}
                        </span>
                        @if ($survey->answer_visibility->value === 'anonymous')
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[12px] font-medium bg-accent-soft text-accent">匿名</span>
                        @endif
                    </div>
                    <div class="text-[15px] font-bold text-text-primary truncate">{{ $survey->title }}</div>
                    <div class="text-[12.5px] text-text-muted mt-1.5">
                        回答期間: {{ $survey->answer_start_date->format('Y-m-d') }} 〜 {{ $survey->answer_end_date->format('Y-m-d') }}
                        ・設問{{ $survey->questions_count }}問
                        @if (! $item['isExpired'])
                            ・<span class="text-warning-text font-medium">残り{{ $item['remainingDays'] }}日</span>
                        @endif
                    </div>
                </div>
                @if ($item['isExpired'])
                    <span class="shrink-0 rounded-[9px] bg-disabled-bg px-4 py-2 text-[13.5px] font-medium text-disabled-text" data-testid="user-survey-expired-{{ $survey->id }}">
                        回答受付終了
                    </span>
                @else
                    <a href="{{ route('user.surveys.answer', $survey) }}" data-testid="user-survey-answer-link-{{ $survey->id }}"
                       class="shrink-0 rounded-[9px] bg-accent px-4 py-2 text-[13.5px] font-medium text-white shadow-[0_1px_2px_rgba(75,83,224,.3)] hover:bg-accent-dark">
                        回答する
                    </a>
                @endif
            </div>
        @empty
            <div class="rounded-[13px] border border-border-card bg-card-bg p-6 text-[13.5px] text-text-secondary" data-testid="user-survey-empty">
                配布されているアンケートはありません。
            </div>
        @endforelse
    </div>
</div>
@endsection

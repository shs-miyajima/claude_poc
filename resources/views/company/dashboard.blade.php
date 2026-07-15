@extends('layouts.app')

@section('title', '集計ダッシュボード')

@php
    $chartPalette = ['#4b53e0', '#2aa7a0', '#e8934a', '#d05fb0', '#6d9e3f', '#5b7cc9'];
    $scalePalette = ['#cdd1f6', '#a7adef', '#7e86e9', '#5860e2', '#3a41c4'];
@endphp

@section('content')
<div data-testid="company-dashboard">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="text-[22px] font-bold text-text-primary">集計ダッシュボード</h1>

        <form method="GET" action="{{ route('company.dashboard') }}" class="flex items-center gap-2">
            <label class="text-[12.5px] text-text-secondary">対象アンケート</label>
            <select name="survey_id" data-testid="dashboard-survey-select" onchange="this.form.submit()"
                    class="border border-border-input rounded-[8px] px-3 py-1.5 text-[13px] text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-soft-border focus:border-accent">
                @foreach ($surveys as $option)
                    <option value="{{ $option->id }}" @selected($survey && $survey->id === $option->id)>{{ $option->title }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if (! $survey)
        <div class="rounded-[13px] border border-border-card bg-card-bg p-6 text-[13.5px] text-text-secondary" data-testid="dashboard-empty">
            対象にできるアンケートがありません。先にアンケートを作成してください。
        </div>
    @else
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div class="rounded-[13px] border border-border-card bg-card-bg p-5">
                <div class="text-[12.5px] text-text-secondary">回答率</div>
                <div class="text-[26px] font-bold text-accent mt-1" data-testid="dashboard-response-rate">{{ $mock['responseRate'] }}%</div>
            </div>
            <div class="rounded-[13px] border border-border-card bg-card-bg p-5">
                <div class="text-[12.5px] text-text-secondary">回答者数</div>
                <div class="text-[26px] font-bold text-text-primary mt-1">{{ $mock['respondentCount'] }}</div>
            </div>
            <div class="rounded-[13px] border border-border-card bg-card-bg p-5">
                <div class="text-[12.5px] text-text-secondary">対象者数</div>
                <div class="text-[26px] font-bold text-text-primary mt-1">{{ $targetUserCount }}</div>
            </div>
            <div class="rounded-[13px] border border-border-card bg-card-bg p-5">
                <div class="text-[12.5px] text-text-secondary">設問数</div>
                <div class="text-[26px] font-bold text-text-primary mt-1">{{ $survey->questions->count() }}</div>
            </div>
        </div>

        <div class="rounded-[13px] border border-border-card bg-card-bg p-5 mb-4">
            <div class="flex flex-wrap items-center gap-4">
                <span class="text-[12.5px] font-medium text-text-secondary">絞り込み</span>
                <select class="border border-border-input rounded-[8px] px-3 py-1.5 text-[12.5px] text-text-secondary" disabled>
                    <option>部署: すべて</option>
                </select>
                <select class="border border-border-input rounded-[8px] px-3 py-1.5 text-[12.5px] text-text-secondary" disabled>
                    <option>性別: すべて</option>
                </select>
                <select class="border border-border-input rounded-[8px] px-3 py-1.5 text-[12.5px] text-text-secondary" disabled>
                    <option>年代: すべて</option>
                </select>
                <span class="text-[11.5px] text-text-muted">属性絞り込みは今後対応予定です</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" data-testid="dashboard-question-stats">
            @foreach ($mock['questionStats'] as $stat)
                @php $question = $stat['question']; @endphp
                <div class="rounded-[13px] border border-border-card bg-card-bg p-5" data-testid="dashboard-question-{{ $question->id }}">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="inline-flex items-center rounded-full bg-accent-soft text-accent px-2.5 py-1 text-[12px] font-medium">
                            {{ $question->question_type->label() }}
                        </span>
                        @if ($stat['scaleStats'])
                            <span class="text-[12.5px] text-text-muted">平均 {{ $stat['scaleStats']['average'] }}</span>
                        @endif
                    </div>
                    <div class="text-[14.5px] font-medium text-text-primary mb-3">{{ $question->body }}</div>

                    @if ($stat['choiceStats'])
                        <div class="flex flex-col gap-2">
                            @foreach ($stat['choiceStats'] as $index => $choiceStat)
                                <div class="flex items-center gap-3 text-[12.5px]">
                                    <span class="w-28 shrink-0 truncate text-text-secondary">{{ $choiceStat['choice']->body }}</span>
                                    <div class="flex-1 h-2 rounded-full bg-subtle-bg-2 overflow-hidden">
                                        <div class="h-full rounded-full" style="width: {{ $choiceStat['percent'] }}%; background-color: {{ $chartPalette[$index % count($chartPalette)] }}"></div>
                                    </div>
                                    <span class="w-16 shrink-0 text-right text-text-muted">{{ $choiceStat['count'] }}・{{ $choiceStat['percent'] }}%</span>
                                </div>
                            @endforeach
                        </div>
                    @elseif ($stat['scaleStats'])
                        <div class="flex flex-col gap-2">
                            @foreach ($stat['scaleStats']['buckets'] as $bucket)
                                @php $percent = $stat['scaleStats']['total'] > 0 ? round($bucket['count'] / $stat['scaleStats']['total'] * 100) : 0; @endphp
                                <div class="flex items-center gap-3 text-[12.5px]">
                                    <span class="w-6 shrink-0 text-text-secondary">{{ $bucket['value'] }}</span>
                                    <div class="flex-1 h-2 rounded-full bg-subtle-bg-2 overflow-hidden">
                                        <div class="h-full rounded-full" style="width: {{ $percent }}%; background-color: {{ $scalePalette[$bucket['value'] - 1] }}"></div>
                                    </div>
                                    <span class="w-16 shrink-0 text-right text-text-muted">{{ $bucket['count'] }}・{{ $percent }}%</span>
                                </div>
                            @endforeach
                        </div>
                    @elseif ($stat['samples'])
                        <div class="flex flex-col gap-2">
                            @foreach ($stat['samples'] as $sample)
                                <div class="rounded-[9px] bg-subtle-bg-2 px-3 py-2 text-[12.5px] text-text-secondary">{{ $sample }}</div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

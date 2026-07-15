@extends('layouts.app')

@section('title', 'アンケート回答')

@section('content')
<div class="max-w-[640px] mx-auto" data-survey-answer data-home-url="{{ route('user.home') }}" data-testid="survey-answer">
    <div class="mb-5">
        <div class="flex items-center justify-between text-[12.5px] text-text-secondary mb-1.5">
            <span class="truncate">{{ $survey->title }}</span>
            <span data-testid="answer-progress-label"></span>
        </div>
        <div class="h-1.5 rounded-full bg-subtle-bg-2 overflow-hidden">
            <div data-testid="answer-progress-bar" class="h-full bg-accent transition-[width] duration-200" style="width: 0%"></div>
        </div>
    </div>

    @if ($survey->answer_visibility->value === 'anonymous')
        <div class="mb-4 rounded-[9px] bg-accent-soft text-accent text-[12.5px] px-3 py-2">このアンケートは匿名で回答されます。</div>
    @endif

    @foreach ($survey->questions as $qIndex => $question)
        <div data-testid="answer-step" class="rounded-[15px] border border-border-card bg-card-bg p-6 mb-4">
            <div class="text-[12.5px] text-text-secondary mb-2">設問 {{ $qIndex + 1 }}（{{ $question->is_required ? '必須' : '任意' }}）</div>
            <div class="text-[16px] font-bold text-text-primary mb-4">{{ $question->body }}</div>

            @if ($question->question_type->value === 'single_choice')
                <div class="flex flex-col gap-2">
                    @foreach ($question->choices as $choice)
                        <label class="flex items-center gap-3 rounded-[10px] border border-border-input px-4 py-3 cursor-pointer has-[:checked]:border-accent-soft-border has-[:checked]:bg-accent-soft">
                            <input type="radio" name="mock_answer_q{{ $qIndex }}" class="accent-accent">
                            <span class="text-[13.5px] text-text-primary">{{ $choice->body }}</span>
                        </label>
                    @endforeach
                </div>
            @elseif ($question->question_type->value === 'multiple_choice')
                <div class="flex flex-col gap-2">
                    @foreach ($question->choices as $choice)
                        <label class="flex items-center gap-3 rounded-[10px] border border-border-input px-4 py-3 cursor-pointer has-[:checked]:border-accent-soft-border has-[:checked]:bg-accent-soft">
                            <input type="checkbox" class="accent-accent">
                            <span class="text-[13.5px] text-text-primary">{{ $choice->body }}</span>
                        </label>
                    @endforeach
                </div>
            @elseif ($question->question_type->value === 'scale')
                <div class="flex items-center gap-2">
                    @for ($value = 1; $value <= 5; $value++)
                        <label class="flex-1 text-center rounded-[10px] border border-border-input py-3 cursor-pointer has-[:checked]:border-accent-soft-border has-[:checked]:bg-accent-soft">
                            <input type="radio" name="mock_answer_q{{ $qIndex }}" class="sr-only">
                            <span class="text-[14px] font-medium text-text-primary">{{ $value }}</span>
                        </label>
                    @endfor
                </div>
                @if ($question->scale_min_label || $question->scale_max_label)
                    <div class="flex justify-between text-[11.5px] text-text-muted mt-1.5">
                        <span>{{ $question->scale_min_label }}</span>
                        <span>{{ $question->scale_max_label }}</span>
                    </div>
                @endif
            @else
                <textarea rows="5" maxlength="500" placeholder="自由記述の回答欄（最大500文字）"
                          class="w-full border border-border-input rounded-[9px] px-3 py-2 text-[13.5px] text-text-primary placeholder:text-text-placeholder focus:outline-none focus:ring-2 focus:ring-accent-soft-border focus:border-accent"></textarea>
            @endif
        </div>
    @endforeach

    <div data-testid="answer-step" class="rounded-[15px] border border-border-card bg-card-bg p-6 mb-4 text-center">
        <div class="text-[16px] font-bold text-text-primary mb-2">回答内容を送信します</div>
        <p class="text-[13px] text-text-secondary">送信後も回答期間内であれば修正できます。</p>
    </div>

    <div class="flex items-center justify-between">
        <button type="button" data-testid="answer-back"
                class="rounded-[9px] border border-border-input px-4 py-2 text-[13px] text-text-secondary disabled:opacity-40 disabled:cursor-not-allowed">
            戻る
        </button>
        <div class="flex items-center gap-2">
            <button type="button" data-testid="answer-save-draft"
                    class="rounded-[9px] border border-border-input px-4 py-2 text-[13px] text-text-secondary hover:bg-subtle-bg-2">
                下書き保存
            </button>
            <button type="button" data-testid="answer-next"
                    class="rounded-[9px] bg-accent px-5 py-2 text-[13px] font-medium text-white shadow-[0_1px_2px_rgba(75,83,224,.3)] hover:bg-accent-dark">
                次へ
            </button>
            <button type="button" data-testid="answer-submit" hidden
                    class="rounded-[9px] bg-success-dot px-5 py-2 text-[13px] font-medium text-white hover:opacity-90">
                回答を送信
            </button>
        </div>
    </div>
</div>
@endsection

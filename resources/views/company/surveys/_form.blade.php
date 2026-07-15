@php
    /** @var \App\Models\Survey|null $survey */
    $isEdit = isset($survey);
    $titleValue = old('title', $survey->title ?? '');
    $startDateValue = old('answer_start_date', $survey?->answer_start_date?->format('Y-m-d') ?? '');
    $endDateValue = old('answer_end_date', $survey?->answer_end_date?->format('Y-m-d') ?? '');
    $visibilityValue = old('answer_visibility', $survey?->answer_visibility?->value ?? '');

    $oldQuestions = old('questions');

    if ($oldQuestions !== null) {
        // バリデーション失敗後の再表示: 送信内容（不正な入力を含む）をそのまま復元し、
        // 個々の設問・選択肢のエラー表示と組み合わせて見せる。
        $questions = collect($oldQuestions)->map(fn ($q) => [
            'body' => $q['body'] ?? '',
            'question_type' => $q['question_type'] ?? '',
            'is_required' => ! empty($q['is_required']),
            'scale_min_label' => $q['scale_min_label'] ?? '',
            'scale_max_label' => $q['scale_max_label'] ?? '',
            'choices' => collect($q['choices'] ?? [])->map(fn ($c) => ['body' => $c['body'] ?? ''])->values()->all(),
        ])->values();
    } elseif ($isEdit) {
        $questions = $survey->questions->map(fn ($question) => [
            'body' => $question->body,
            'question_type' => $question->question_type->value,
            'is_required' => $question->is_required,
            'scale_min_label' => $question->scale_min_label ?? '',
            'scale_max_label' => $question->scale_max_label ?? '',
            'choices' => $question->choices->map(fn ($choice) => ['body' => $choice->body])->values()->all(),
        ]);
    } else {
        $questions = collect();
    }
@endphp
<form method="POST" action="{{ $isEdit ? route('company.surveys.update', $survey) : route('company.surveys.store') }}" data-survey-form>
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="rounded-[15px] border border-t-4 border-border-card border-t-accent bg-card-bg p-6 mb-5">
        <div class="mb-4">
            <input type="text" name="title" value="{{ $titleValue }}" data-testid="survey-title-input" placeholder="アンケートタイトル"
                   class="w-full border-0 border-b {{ $errors->has('title') ? 'border-danger' : 'border-border-input' }} pb-2 text-[20px] font-bold text-text-primary placeholder:text-text-placeholder focus:outline-none focus:border-accent">
            @error('title')<div class="text-danger text-[12.5px] mt-1" data-testid="title-error">{{ $message }}</div>@enderror
        </div>

        <div class="flex flex-wrap items-end gap-6">
            <div>
                <label class="block text-[12.5px] text-text-secondary mb-1">回答開始日</label>
                <input type="date" name="answer_start_date" value="{{ $startDateValue }}" data-testid="survey-start-date-input"
                       class="border rounded-[8px] px-3 py-1.5 text-[13.5px] {{ $errors->has('answer_start_date') ? 'border-danger' : 'border-border-input' }} focus:outline-none focus:ring-2 focus:ring-accent-soft-border focus:border-accent">
                @error('answer_start_date')<div class="text-danger text-[12.5px] mt-1" data-testid="answer-start-date-error">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="block text-[12.5px] text-text-secondary mb-1">回答終了日</label>
                <input type="date" name="answer_end_date" value="{{ $endDateValue }}" data-testid="survey-end-date-input"
                       class="border rounded-[8px] px-3 py-1.5 text-[13.5px] {{ $errors->has('answer_end_date') ? 'border-danger' : 'border-border-input' }} focus:outline-none focus:ring-2 focus:ring-accent-soft-border focus:border-accent">
                @error('answer_end_date')<div class="text-danger text-[12.5px] mt-1" data-testid="answer-end-date-error">{{ $message }}</div>@enderror
            </div>
            <div>
                <span class="block text-[12.5px] text-text-secondary mb-1">記名/匿名</span>
                <div class="inline-flex rounded-[9px] border border-border-input p-0.5 text-[12.5px]">
                    <label class="relative cursor-pointer">
                        <input type="radio" name="answer_visibility" value="named" data-testid="survey-visibility-named" class="peer absolute inset-0 z-10 opacity-0 cursor-pointer" @checked($visibilityValue === 'named')>
                        <span class="inline-block rounded-[7px] px-3 py-1 text-text-secondary peer-checked:bg-accent-soft peer-checked:text-accent peer-checked:font-medium">記名</span>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="answer_visibility" value="anonymous" data-testid="survey-visibility-anonymous" class="peer absolute inset-0 z-10 opacity-0 cursor-pointer" @checked($visibilityValue === 'anonymous')>
                        <span class="inline-block rounded-[7px] px-3 py-1 text-text-secondary peer-checked:bg-accent-soft peer-checked:text-accent peer-checked:font-medium">匿名</span>
                    </label>
                </div>
                @error('answer_visibility')<div class="text-danger text-[12.5px] mt-1" data-testid="answer-visibility-error">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div class="mb-5">
        <h2 class="text-[15px] font-bold text-text-primary mb-3">設問</h2>
        <div data-testid="questions-container">
            @foreach ($questions as $qIndex => $question)
                @include('company.surveys._question', ['question' => $question, 'qIndex' => $qIndex])
            @endforeach
        </div>
        <button type="button" data-testid="question-add"
                class="w-full rounded-[10px] border border-dashed border-border-input py-3 text-[13.5px] text-text-secondary hover:border-accent hover:text-accent">
            ＋ 設問を追加
        </button>
    </div>

    <template data-testid="question-template">
        @include('company.surveys._question', ['question' => null, 'qIndex' => 'NEW'])
    </template>

    <template data-testid="choice-template">
        @include('company.surveys._choice', ['choice' => null, 'qIndex' => 'NEW', 'cIndex' => 'NEW'])
    </template>

    <button type="submit" data-testid="survey-submit"
            class="rounded-[9px] bg-accent px-5 py-2.5 text-[13.5px] font-medium text-white shadow-[0_1px_2px_rgba(75,83,224,.3)] hover:bg-accent-dark">
        保存
    </button>
</form>

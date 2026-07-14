@php
    /** @var \App\Models\Survey|null $survey */
    $isEdit = isset($survey);
    $titleValue = old('title', $survey->title ?? '');
    $startDateValue = old('answer_start_date', $survey?->answer_start_date?->format('Y-m-d') ?? '');
    $endDateValue = old('answer_end_date', $survey?->answer_end_date?->format('Y-m-d') ?? '');
    $visibilityValue = old('answer_visibility', $survey?->answer_visibility?->value ?? '');
    $questions = $survey->questions ?? collect();
@endphp
<form method="POST" action="{{ $isEdit ? route('company.surveys.update', $survey) : route('company.surveys.store') }}" data-survey-form>
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="mb-3">
        <label class="block text-sm mb-1">タイトル</label>
        <input type="text" name="title" value="{{ $titleValue }}" data-testid="survey-title-input" class="border rounded w-full px-2 py-1">
        @error('title')<div class="text-red-600 text-sm" data-testid="title-error">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3 flex gap-4">
        <div>
            <label class="block text-sm mb-1">回答期間（開始日）</label>
            <input type="date" name="answer_start_date" value="{{ $startDateValue }}" data-testid="survey-start-date-input" class="border rounded px-2 py-1">
            @error('answer_start_date')<div class="text-red-600 text-sm" data-testid="answer-start-date-error">{{ $message }}</div>@enderror
        </div>
        <div>
            <label class="block text-sm mb-1">回答期間（終了日）</label>
            <input type="date" name="answer_end_date" value="{{ $endDateValue }}" data-testid="survey-end-date-input" class="border rounded px-2 py-1">
            @error('answer_end_date')<div class="text-red-600 text-sm" data-testid="answer-end-date-error">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="mb-3">
        <span class="block text-sm mb-1">記名/匿名</span>
        <label class="mr-3"><input type="radio" name="answer_visibility" value="named" data-testid="survey-visibility-named" @checked($visibilityValue === 'named')> 記名</label>
        <label class="mr-3"><input type="radio" name="answer_visibility" value="anonymous" data-testid="survey-visibility-anonymous" @checked($visibilityValue === 'anonymous')> 匿名</label>
        @error('answer_visibility')<div class="text-red-600 text-sm" data-testid="answer-visibility-error">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <h2 class="font-bold mb-2">設問</h2>
        <div data-testid="questions-container">
            @foreach ($questions as $qIndex => $question)
                @include('company.surveys._question', ['question' => $question, 'qIndex' => $qIndex])
            @endforeach
        </div>
        <button type="button" data-testid="question-add" class="px-3 py-1 border rounded">設問を追加</button>
    </div>

    <template data-testid="question-template">
        @include('company.surveys._question', ['question' => null, 'qIndex' => 'NEW'])
    </template>

    <template data-testid="choice-template">
        @include('company.surveys._choice', ['choice' => null, 'qIndex' => 'NEW', 'cIndex' => 'NEW'])
    </template>

    <button type="submit" data-testid="survey-submit" class="bg-blue-600 text-white px-4 py-2 rounded">保存</button>
</form>

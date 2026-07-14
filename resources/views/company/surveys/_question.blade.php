@php
    /** @var \App\Models\Question|null $question */
    $bodyValue = $question->body ?? '';
    $selectedType = $question?->question_type?->value ?? 'single_choice';
    $isRequired = $question->is_required ?? false;
    $scaleMinLabel = $question->scale_min_label ?? '';
    $scaleMaxLabel = $question->scale_max_label ?? '';
    $choices = $question->choices ?? collect();
    $showChoices = in_array($selectedType, ['single_choice', 'multiple_choice'], true);
    $showScale = $selectedType === 'scale';
    $bodyErrorKey = "questions.$qIndex.body";
    $typeErrorKey = "questions.$qIndex.question_type";
    $choicesErrorKey = "questions.$qIndex.choices";
    $scaleMinErrorKey = "questions.$qIndex.scale_min_label";
    $scaleMaxErrorKey = "questions.$qIndex.scale_max_label";
@endphp
<div class="question-block border rounded p-4 mb-4 bg-white" data-testid="question-block">
    <div class="flex justify-between items-center mb-2">
        <span class="font-bold">設問</span>
        <div class="flex gap-2">
            <button type="button" data-testid="question-move-up" class="px-2 py-1 border rounded">↑</button>
            <button type="button" data-testid="question-move-down" class="px-2 py-1 border rounded">↓</button>
            <button type="button" data-testid="question-remove" class="px-2 py-1 border rounded text-red-600">設問を削除</button>
        </div>
    </div>

    <div class="mb-2">
        <label class="block text-sm mb-1">設問文</label>
        <input type="text" name="questions[{{ $qIndex }}][body]" value="{{ $bodyValue }}" data-testid="question-body-input" class="border rounded w-full px-2 py-1 @error($bodyErrorKey) border-red-600 @enderror">
        @error($bodyErrorKey)<div class="text-red-600 text-sm" data-testid="question-body-error">{{ $message }}</div>@enderror
    </div>

    <div class="mb-2 @error($typeErrorKey) border border-red-600 rounded p-1 @enderror">
        <span class="block text-sm mb-1">設問形式</span>
        <label class="mr-3"><input type="radio" name="questions[{{ $qIndex }}][question_type]" value="single_choice" data-testid="question-type-single_choice" @checked($selectedType === 'single_choice')> 単一選択</label>
        <label class="mr-3"><input type="radio" name="questions[{{ $qIndex }}][question_type]" value="multiple_choice" data-testid="question-type-multiple_choice" @checked($selectedType === 'multiple_choice')> 複数選択</label>
        <label class="mr-3"><input type="radio" name="questions[{{ $qIndex }}][question_type]" value="free_text" data-testid="question-type-free_text" @checked($selectedType === 'free_text')> 自由記述</label>
        <label class="mr-3"><input type="radio" name="questions[{{ $qIndex }}][question_type]" value="scale" data-testid="question-type-scale" @checked($selectedType === 'scale')> 段階評価</label>
        @error($typeErrorKey)<div class="text-red-600 text-sm" data-testid="question-type-error">{{ $message }}</div>@enderror
    </div>

    <div class="mb-2">
        <span class="block text-sm mb-1">必須/任意</span>
        <label class="mr-3"><input type="radio" name="questions[{{ $qIndex }}][is_required]" value="1" data-testid="question-required-required" @checked($isRequired)> 必須</label>
        <label class="mr-3"><input type="radio" name="questions[{{ $qIndex }}][is_required]" value="0" data-testid="question-required-optional" @checked(! $isRequired)> 任意</label>
    </div>

    <div data-testid="choices-section" class="mb-2" @if (! $showChoices) hidden @endif>
        <span class="block text-sm mb-1">選択肢</span>
        <div data-testid="choices-container">
            @forelse ($choices as $cIndex => $choice)
                @include('company.surveys._choice', ['choice' => $choice, 'qIndex' => $qIndex, 'cIndex' => $cIndex])
            @empty
                @include('company.surveys._choice', ['choice' => null, 'qIndex' => $qIndex, 'cIndex' => 0])
                @include('company.surveys._choice', ['choice' => null, 'qIndex' => $qIndex, 'cIndex' => 1])
            @endforelse
        </div>
        <button type="button" data-testid="choice-add" class="mt-1 px-2 py-1 border rounded">選択肢を追加</button>
        @error($choicesErrorKey)<div class="text-red-600 text-sm" data-testid="choices-error">{{ $message }}</div>@enderror
    </div>

    <div data-testid="scale-section" class="mb-2" @if (! $showScale) hidden @endif>
        <div class="mb-1">
            <label class="block text-sm mb-1">1側ラベル（任意）</label>
            <input type="text" name="questions[{{ $qIndex }}][scale_min_label]" value="{{ $scaleMinLabel }}" data-testid="scale-min-label-input" class="border rounded w-full px-2 py-1 @error($scaleMinErrorKey) border-red-600 @enderror">
            @error($scaleMinErrorKey)<div class="text-red-600 text-sm" data-testid="scale-min-label-error">{{ $message }}</div>@enderror
        </div>
        <div>
            <label class="block text-sm mb-1">5側ラベル（任意）</label>
            <input type="text" name="questions[{{ $qIndex }}][scale_max_label]" value="{{ $scaleMaxLabel }}" data-testid="scale-max-label-input" class="border rounded w-full px-2 py-1 @error($scaleMaxErrorKey) border-red-600 @enderror">
            @error($scaleMaxErrorKey)<div class="text-red-600 text-sm" data-testid="scale-max-label-error">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

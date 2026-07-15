@php
    /**
     * $question は以下のいずれか:
     * - null（<template> 用の空データ）
     * - 連想配列（body/question_type/is_required/scale_min_label/scale_max_label/choices）
     *   _form.blade.php で old('questions') または DB の Question から正規化済み
     */
    $question = $question ?? [];
    $bodyValue = $question['body'] ?? '';
    $selectedType = $question['question_type'] ?? 'single_choice';
    $isRequired = ! empty($question['is_required']);
    $scaleMinLabel = $question['scale_min_label'] ?? '';
    $scaleMaxLabel = $question['scale_max_label'] ?? '';
    $choices = $question['choices'] ?? [];
    $showChoices = in_array($selectedType, ['single_choice', 'multiple_choice'], true);
    $showScale = $selectedType === 'scale';
    $bodyErrorKey = "questions.$qIndex.body";
    $typeErrorKey = "questions.$qIndex.question_type";
    $choicesErrorKey = "questions.$qIndex.choices";
    $scaleMinErrorKey = "questions.$qIndex.scale_min_label";
    $scaleMaxErrorKey = "questions.$qIndex.scale_max_label";

    $typeOptions = [
        'single_choice' => '◉ 単一選択',
        'multiple_choice' => '☑ 複数選択',
        'free_text' => '¶ 自由記述',
        'scale' => '★ 段階評価',
    ];
@endphp
<div class="question-block rounded-[13px] border border-border-card bg-card-bg p-5 mb-4" data-testid="question-block">
    <div class="flex justify-between items-center mb-3">
        <span class="text-[13px] font-medium text-text-secondary">設問</span>
        <div class="flex items-center gap-1.5">
            <button type="button" data-testid="question-move-up" class="rounded-[7px] border border-border-input w-7 h-7 text-[12px] text-text-secondary hover:bg-subtle-bg-2">▲</button>
            <button type="button" data-testid="question-move-down" class="rounded-[7px] border border-border-input w-7 h-7 text-[12px] text-text-secondary hover:bg-subtle-bg-2">▼</button>
            <button type="button" data-testid="question-remove" class="ml-1 text-[12.5px] text-danger hover:opacity-80">設問を削除</button>
        </div>
    </div>

    <div class="mb-3">
        <input type="text" name="questions[{{ $qIndex }}][body]" value="{{ $bodyValue }}" data-testid="question-body-input" placeholder="設問文"
               class="w-full border-0 border-b {{ $errors->has($bodyErrorKey) ? 'border-danger' : 'border-border-input' }} pb-1.5 text-[14.5px] text-text-primary placeholder:text-text-placeholder focus:outline-none focus:border-accent">
        @error($bodyErrorKey)<div class="text-danger text-[12.5px] mt-1" data-testid="question-body-error">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3 {{ $errors->has($typeErrorKey) ? 'ring-1 ring-danger rounded-[9px] p-1' : '' }}">
        <div class="flex flex-wrap gap-2">
            @foreach ($typeOptions as $typeValue => $typeLabel)
                <label class="relative cursor-pointer">
                    <input type="radio" name="questions[{{ $qIndex }}][question_type]" value="{{ $typeValue }}" data-testid="question-type-{{ $typeValue }}" class="peer absolute inset-0 z-10 opacity-0 cursor-pointer" @checked($selectedType === $typeValue)>
                    <span class="inline-flex items-center rounded-full border border-border-input px-3 py-1.5 text-[12.5px] text-text-secondary peer-checked:bg-accent-soft peer-checked:text-accent peer-checked:border-accent-soft-border peer-checked:font-medium">
                        {{ $typeLabel }}
                    </span>
                </label>
            @endforeach
        </div>
        @error($typeErrorKey)<div class="text-danger text-[12.5px] mt-1" data-testid="question-type-error">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3 flex items-center justify-end gap-2">
        <span class="text-[12.5px] text-text-secondary">必須/任意</span>
        <div class="inline-flex rounded-[9px] border border-border-input p-0.5 text-[12.5px]">
            <label class="relative cursor-pointer">
                <input type="radio" name="questions[{{ $qIndex }}][is_required]" value="1" data-testid="question-required-required" class="peer absolute inset-0 z-10 opacity-0 cursor-pointer" @checked($isRequired)>
                <span class="inline-block rounded-[7px] px-3 py-1 text-text-secondary peer-checked:bg-accent-soft peer-checked:text-accent peer-checked:font-medium">必須</span>
            </label>
            <label class="relative cursor-pointer">
                <input type="radio" name="questions[{{ $qIndex }}][is_required]" value="0" data-testid="question-required-optional" class="peer absolute inset-0 z-10 opacity-0 cursor-pointer" @checked(! $isRequired)>
                <span class="inline-block rounded-[7px] px-3 py-1 text-text-secondary peer-checked:bg-accent-soft peer-checked:text-accent peer-checked:font-medium">任意</span>
            </label>
        </div>
    </div>

    <div data-testid="choices-section" class="mb-1" @if (! $showChoices) hidden @endif>
        <div class="flex flex-col gap-1.5">
            <span class="block text-[12.5px] text-text-secondary mb-0.5">選択肢</span>
            <div data-testid="choices-container" class="flex flex-col gap-1.5">
                @forelse ($choices as $cIndex => $choice)
                    @include('company.surveys._choice', ['choice' => $choice, 'qIndex' => $qIndex, 'cIndex' => $cIndex])
                @empty
                    @include('company.surveys._choice', ['choice' => null, 'qIndex' => $qIndex, 'cIndex' => 0])
                    @include('company.surveys._choice', ['choice' => null, 'qIndex' => $qIndex, 'cIndex' => 1])
                @endforelse
            </div>
            <button type="button" data-testid="choice-add" class="self-start mt-1 text-[12.5px] text-accent hover:text-accent-dark">＋ 選択肢を追加</button>
            @error($choicesErrorKey)<div class="text-danger text-[12.5px] mt-1" data-testid="choices-error">{{ $message }}</div>@enderror
        </div>
    </div>

    <div data-testid="scale-section" class="mb-1" @if (! $showScale) hidden @endif>
        <div class="flex flex-col gap-3">
            <div class="flex items-center gap-1 text-accent-light-to text-[18px]" aria-hidden="true">
                <span>★</span><span>★</span><span>★</span><span>★</span><span>★</span>
                <span class="ml-2 text-[12px] text-text-muted">1〜5の段階評価</span>
            </div>
            <div>
                <label class="block text-[12.5px] text-text-secondary mb-1">1側ラベル（任意）</label>
                <input type="text" name="questions[{{ $qIndex }}][scale_min_label]" value="{{ $scaleMinLabel }}" data-testid="scale-min-label-input"
                       class="border rounded-[8px] w-full px-3 py-1.5 text-[13.5px] {{ $errors->has($scaleMinErrorKey) ? 'border-danger' : 'border-border-input' }} focus:outline-none focus:ring-2 focus:ring-accent-soft-border focus:border-accent">
                @error($scaleMinErrorKey)<div class="text-danger text-[12.5px] mt-1" data-testid="scale-min-label-error">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="block text-[12.5px] text-text-secondary mb-1">5側ラベル（任意）</label>
                <input type="text" name="questions[{{ $qIndex }}][scale_max_label]" value="{{ $scaleMaxLabel }}" data-testid="scale-max-label-input"
                       class="border rounded-[8px] w-full px-3 py-1.5 text-[13.5px] {{ $errors->has($scaleMaxErrorKey) ? 'border-danger' : 'border-border-input' }} focus:outline-none focus:ring-2 focus:ring-accent-soft-border focus:border-accent">
                @error($scaleMaxErrorKey)<div class="text-danger text-[12.5px] mt-1" data-testid="scale-max-label-error">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

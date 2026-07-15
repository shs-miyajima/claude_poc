@php
    /** $choice は null（<template> 用）または連想配列（body） */
    $choice = $choice ?? [];
    $choiceBody = $choice['body'] ?? '';
    $choiceBodyErrorKey = "questions.$qIndex.choices.$cIndex.body";
@endphp
<div class="choice-block flex items-start gap-2" data-testid="choice-block">
    <div class="flex-1">
        <input type="text" name="questions[{{ $qIndex }}][choices][{{ $cIndex }}][body]" value="{{ $choiceBody }}" data-testid="choice-body-input" placeholder="選択肢"
               class="border rounded-[8px] w-full px-3 py-1.5 text-[13.5px] {{ $errors->has($choiceBodyErrorKey) ? 'border-danger' : 'border-border-input' }} focus:outline-none focus:ring-2 focus:ring-accent-soft-border focus:border-accent">
        @error($choiceBodyErrorKey)<div class="text-danger text-[12.5px] mt-1" data-testid="choice-body-error">{{ $message }}</div>@enderror
    </div>
    <button type="button" data-testid="choice-remove" class="rounded-[7px] border border-border-input w-8 h-8 shrink-0 text-[12.5px] text-danger hover:bg-subtle-bg-2 disabled:opacity-40 disabled:cursor-not-allowed">✕</button>
</div>

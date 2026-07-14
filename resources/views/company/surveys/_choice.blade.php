@php
    /** $choice は null（<template> 用）または連想配列（body） */
    $choice = $choice ?? [];
    $choiceBody = $choice['body'] ?? '';
    $choiceBodyErrorKey = "questions.$qIndex.choices.$cIndex.body";
@endphp
<div class="choice-block flex items-start gap-2 mb-1" data-testid="choice-block">
    <div class="flex-1">
        <input type="text" name="questions[{{ $qIndex }}][choices][{{ $cIndex }}][body]" value="{{ $choiceBody }}" data-testid="choice-body-input" class="border rounded w-full px-2 py-1 @error($choiceBodyErrorKey) border-red-600 @enderror">
        @error($choiceBodyErrorKey)<div class="text-red-600 text-sm" data-testid="choice-body-error">{{ $message }}</div>@enderror
    </div>
    <button type="button" data-testid="choice-remove" class="px-2 py-1 border rounded text-red-600">削除</button>
</div>

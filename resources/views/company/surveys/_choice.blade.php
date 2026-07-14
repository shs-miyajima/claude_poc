@php
    $choiceBody = $choice->body ?? '';
@endphp
<div class="choice-block flex items-center gap-2 mb-1" data-testid="choice-block">
    <input type="text" name="questions[{{ $qIndex }}][choices][{{ $cIndex }}][body]" value="{{ $choiceBody }}" data-testid="choice-body-input" class="border rounded flex-1 px-2 py-1">
    <button type="button" data-testid="choice-remove" class="px-2 py-1 border rounded text-red-600">削除</button>
</div>

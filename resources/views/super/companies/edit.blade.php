@extends('layouts.app')

@section('title', '企業編集')

@section('content')
<div class="max-w-md mx-auto">
    <h1 class="text-[22px] font-bold text-text-primary mb-6">企業編集</h1>

    <div class="rounded-[13px] border border-border-card bg-card-bg p-6">
        <div class="mb-4 text-[12.5px] font-mono text-text-muted" data-testid="company-code-display">企業コード: {{ $company->code }}</div>

        <form method="POST" action="{{ route('super.companies.update', $company) }}">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block text-[13px] font-medium text-text-secondary mb-1">企業名</label>
                <input type="text" name="name" value="{{ old('name', $company->name) }}" data-testid="company-name-input"
                       class="border border-border-input rounded-[8px] w-full px-3 py-2 text-[13.5px] text-text-primary focus:outline-none focus:ring-2 focus:ring-accent-soft-border focus:border-accent">
                @error('name')
                    <div class="text-danger text-[12.5px] mt-1" data-testid="name-error">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" data-testid="company-submit"
                    class="w-full rounded-[9px] bg-accent px-4 py-2 text-[13.5px] font-medium text-white shadow-[0_1px_2px_rgba(75,83,224,.3)] hover:bg-accent-dark">
                更新
            </button>
        </form>
    </div>
</div>
@endsection

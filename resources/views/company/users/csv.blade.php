@extends('layouts.app')

@section('title', 'ユーザーCSV一括登録')

@section('content')
<div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-lg font-bold mb-4">ユーザーCSV一括登録</h1>

    <form method="POST" action="{{ route('company.users.csv.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label class="block text-sm mb-1">CSVファイル</label>
            <input type="file" name="csv_file" data-testid="csv-file-input" class="border rounded w-full px-2 py-1">
            @error('csv_file')<div class="text-red-600 text-sm" data-testid="csv-file-error">{{ $message }}</div>@enderror
        </div>
        <button type="submit" data-testid="csv-submit" class="bg-blue-600 text-white px-4 py-2 rounded w-full">登録</button>
    </form>

    @isset($csvErrors)
        @if (count($csvErrors) > 0)
            <div class="mt-4" data-testid="csv-error-list">
                <p class="text-red-600 font-bold mb-2">エラーが発生したため登録されませんでした。</p>
                <ul class="list-disc list-inside text-red-600 text-sm">
                    @foreach ($csvErrors as $error)
                        <li data-testid="csv-error-row">{{ $error['message'] }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endisset
</div>
@endsection

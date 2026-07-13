<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserCsvImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'csv_file' => ['required', 'file', 'extensions:csv'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'csv_file.required' => 'CSVファイルを選択してください。',
            'csv_file.file' => 'CSVファイル（UTF-8）の形式が正しくありません。',
            'csv_file.extensions' => 'CSVファイル（UTF-8）の形式が正しくありません。',
        ];
    }
}

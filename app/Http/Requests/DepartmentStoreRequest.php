<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentStoreRequest extends FormRequest
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
        $companyId = app('currentCompany')->id;

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('departments', 'name')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => '部署名は必須です。',
            'name.max' => '部署名は100文字以内で入力してください。',
            'name.unique' => 'この部署名は既に登録されています。',
        ];
    }
}

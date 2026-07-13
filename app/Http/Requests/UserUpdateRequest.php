<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Models\Department;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
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
        $user = $this->route('user');
        $currentDepartmentId = $user->department_id;

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->where(fn ($query) => $query->where('company_id', $companyId))->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'birth_date' => ['required', 'date_format:Y-m-d'],
            'hire_date' => ['required', 'date_format:Y-m-d'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'department_id' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail) use ($companyId, $currentDepartmentId) {
                    if ((int) $value === $currentDepartmentId) {
                        return;
                    }

                    $exists = Department::query()
                        ->where('id', $value)
                        ->where('company_id', $companyId)
                        ->whereNull('deactivated_at')
                        ->exists();

                    if (! $exists) {
                        $fail('部署が正しくありません。');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => '氏名は必須です。',
            'name.max' => '氏名は100文字以内で入力してください。',
            'email.required' => 'メールアドレスは必須です。',
            'email.email' => 'メールアドレスの形式が正しくありません。',
            'email.max' => 'メールアドレスは255文字以内で入力してください。',
            'email.unique' => 'このメールアドレスは既に登録されています。',
            'password.min' => 'パスワードは8文字以上で入力してください。',
            'password.max' => 'パスワードは255文字以内で入力してください。',
            'birth_date.required' => '生年月日は必須です。',
            'birth_date.date_format' => '生年月日はYYYY-MM-DD形式の正しい日付で入力してください。',
            'hire_date.required' => '入社年月日は必須です。',
            'hire_date.date_format' => '入社年月日はYYYY-MM-DD形式の正しい日付で入力してください。',
            'gender.required' => '性別は必須です。',
            'gender.enum' => '性別が正しくありません。',
            'department_id.required' => '部署は必須です。',
        ];
    }
}

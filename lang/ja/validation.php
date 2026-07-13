<?php

return [
    'required' => ':attributeは必須です。',
    'string' => ':attributeは文字列で入力してください。',
    'max' => [
        'string' => ':attributeは:max文字以内で入力してください。',
        'file' => ':attributeは:maxキロバイト以内でアップロードしてください。',
    ],
    'min' => [
        'string' => ':attributeは:min文字以上で入力してください。',
    ],
    'email' => ':attributeの形式が正しくありません。',
    'unique' => ':attributeは既に使用されています。',
    'exists' => '選択された:attributeは正しくありません。',
    'date_format' => ':attributeはYYYY-MM-DD形式の正しい日付で入力してください。',
    'enum' => '選択された:attributeは正しくありません。',
    'file' => ':attributeはファイル形式が正しくありません。',
    'extensions' => ':attributeの拡張子が正しくありません。',

    'attributes' => [
        'company_code' => '企業コード',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'name' => '氏名',
        'birth_date' => '生年月日',
        'hire_date' => '入社年月日',
        'gender' => '性別',
        'department_id' => '部署',
        'csv_file' => 'CSVファイル',
    ],
];

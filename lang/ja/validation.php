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
        'title' => 'タイトル',
        'answer_start_date' => '回答期間（開始日）',
        'answer_end_date' => '回答期間（終了日）',
        'answer_visibility' => '記名/匿名',
        'questions.*.body' => '設問文',
        'questions.*.question_type' => '設問形式',
        'questions.*.choices.*.body' => '選択肢',
        'questions.*.scale_min_label' => '段階評価の1側ラベル',
        'questions.*.scale_max_label' => '段階評価の5側ラベル',
    ],
];

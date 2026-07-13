<?php

namespace App\Services;

use App\Enums\Gender;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use DateTime;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserCsvImportService
{
    private const HEADER = ['氏名', 'メールアドレス', '初期パスワード', '生年月日', '入社年月日', '性別', '部署名'];

    private const MAX_ROWS = 1000;

    public function import(Company $company, UploadedFile $file): CsvImportResult
    {
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false || ! mb_check_encoding($contents, 'UTF-8')) {
            return new CsvImportResult(0, [
                ['line' => 0, 'message' => 'CSVファイル（UTF-8）の形式が正しくありません。'],
            ]);
        }

        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);

        $lines = preg_split('/\r\n|\r|\n/', (string) $contents);
        $lines = array_values(array_filter($lines, fn (string $line) => $line !== ''));

        if ($lines === []) {
            return new CsvImportResult(0, [
                ['line' => 0, 'message' => 'CSVのヘッダー行が正しくありません。'],
            ]);
        }

        $header = str_getcsv(array_shift($lines));

        if ($header !== self::HEADER) {
            return new CsvImportResult(0, [
                ['line' => 0, 'message' => 'CSVのヘッダー行が正しくありません。'],
            ]);
        }

        $dataLines = $lines;

        if (count($dataLines) < 1 || count($dataLines) > self::MAX_ROWS) {
            return new CsvImportResult(0, [
                ['line' => 0, 'message' => 'CSVのデータ行は1件以上1,000件以下にしてください。'],
            ]);
        }

        $errors = [];
        $rows = [];
        $seenEmails = [];

        foreach ($dataLines as $index => $line) {
            $lineNumber = $index + 2;
            $columns = str_getcsv($line);

            if (count($columns) !== count(self::HEADER)) {
                $errors[] = ['line' => $lineNumber, 'message' => "{$lineNumber}行目: 列数が正しくありません。"];

                continue;
            }

            [$name, $email, $password, $birthDate, $hireDate, $genderLabel, $departmentName] = $columns;

            $message = $this->validateRow($company, $name, $email, $password, $birthDate, $hireDate, $genderLabel, $departmentName, $seenEmails);

            if ($message !== null) {
                $errors[] = ['line' => $lineNumber, 'message' => "{$lineNumber}行目: {$message}"];

                continue;
            }

            $seenEmails[] = $email;

            $rows[] = [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'birth_date' => $birthDate,
                'hire_date' => $hireDate,
                'gender' => $this->genderValueFromLabel($genderLabel),
                'department_id' => $this->resolveDepartmentId($company, $departmentName),
            ];
        }

        if ($errors !== []) {
            return new CsvImportResult(0, $errors);
        }

        DB::transaction(function () use ($company, $rows) {
            foreach ($rows as $row) {
                User::query()->create([
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'password' => Hash::make($row['password']),
                    'role' => UserRole::User,
                    'company_id' => $company->id,
                    'department_id' => $row['department_id'],
                    'birth_date' => $row['birth_date'],
                    'hire_date' => $row['hire_date'],
                    'gender' => $row['gender'],
                ]);
            }
        });

        return new CsvImportResult(count($rows), []);
    }

    /**
     * @param  list<string>  $seenEmails
     */
    private function validateRow(
        Company $company,
        string $name,
        string $email,
        string $password,
        string $birthDate,
        string $hireDate,
        string $genderLabel,
        string $departmentName,
        array $seenEmails,
    ): ?string {
        if ($name === '') {
            return '氏名は必須です。';
        }

        if (mb_strlen($name) > 100) {
            return '氏名は100文字以内で入力してください。';
        }

        if ($email === '') {
            return 'メールアドレスは必須です。';
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return 'メールアドレスの形式が正しくありません。';
        }

        if (mb_strlen($email) > 255) {
            return 'メールアドレスは255文字以内で入力してください。';
        }

        $emailExists = User::query()->where('company_id', $company->id)->where('email', $email)->exists();

        if ($emailExists) {
            return 'このメールアドレスは既に登録されています。';
        }

        if (in_array($email, $seenEmails, true)) {
            return 'メールアドレスがファイル内で重複しています。';
        }

        if ($password === '') {
            return '初期パスワードは必須です。';
        }

        if (mb_strlen($password) < 8) {
            return 'パスワードは8文字以上で入力してください。';
        }

        if (mb_strlen($password) > 255) {
            return 'パスワードは255文字以内で入力してください。';
        }

        if (! $this->isValidDate($birthDate)) {
            return '生年月日はYYYY-MM-DD形式の正しい日付で入力してください。';
        }

        if (! $this->isValidDate($hireDate)) {
            return '入社年月日はYYYY-MM-DD形式の正しい日付で入力してください。';
        }

        if ($this->genderValueFromLabel($genderLabel) === null) {
            return '性別が正しくありません。';
        }

        if ($this->resolveDepartmentId($company, $departmentName) === null) {
            return '部署が正しくありません。';
        }

        return null;
    }

    private function isValidDate(string $value): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return false;
        }

        $date = DateTime::createFromFormat('Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function genderValueFromLabel(string $label): ?string
    {
        foreach (Gender::cases() as $gender) {
            if ($gender->label() === $label) {
                return $gender->value;
            }
        }

        return null;
    }

    private function resolveDepartmentId(Company $company, string $name): ?int
    {
        $department = Department::query()
            ->where('company_id', $company->id)
            ->where('name', $name)
            ->whereNull('deactivated_at')
            ->first();

        return $department?->id;
    }
}

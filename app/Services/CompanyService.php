<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\DB;

class CompanyService
{
    /**
     * @param  array{name: string}  $data
     */
    public function create(array $data): Company
    {
        return DB::transaction(function () use ($data) {
            // 採番の読み取り→算出→INSERTがトランザクションをまたいで競合しないよう、
            // 同時に企業登録が行われても採番が重複しないようテーブルロックで直列化する
            DB::statement('LOCK TABLE companies IN SHARE ROW EXCLUSIVE MODE');

            return Company::query()->create([
                'name' => $data['name'],
                'code' => $this->nextCode(),
            ]);
        });
    }

    /**
     * @param  array{name: string}  $data
     */
    public function update(Company $company, array $data): Company
    {
        $company->update(['name' => $data['name']]);

        return $company;
    }

    public function deactivate(Company $company): void
    {
        DB::transaction(function () use ($company) {
            $company->update(['deactivated_at' => now()]);
            $company->users()->whereNull('deactivated_at')->update(['deactivated_at' => now()]);
        });
    }

    public function activate(Company $company): void
    {
        $company->update(['deactivated_at' => null]);
    }

    public function nextCode(): string
    {
        $maxCode = Company::query()->max('code');

        $nextNumber = $maxCode === null ? 1 : ((int) substr($maxCode, 1)) + 1;

        return sprintf('C%04d', $nextNumber);
    }
}

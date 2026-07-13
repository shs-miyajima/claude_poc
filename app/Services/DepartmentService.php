<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Department;

class DepartmentService
{
    /**
     * @param  array{name: string}  $data
     */
    public function create(Company $company, array $data): Department
    {
        return Department::query()->create([
            'company_id' => $company->id,
            'name' => $data['name'],
        ]);
    }

    /**
     * @param  array{name: string}  $data
     */
    public function update(Department $department, array $data): Department
    {
        $department->update(['name' => $data['name']]);

        return $department;
    }

    public function deactivate(Department $department): void
    {
        $department->update(['deactivated_at' => now()]);
    }

    public function activate(Department $department): void
    {
        $department->update(['deactivated_at' => null]);
    }
}

<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * @param  array{name: string, email: string, password: string, birth_date: string, hire_date: string, gender: string, department_id: int}  $data
     */
    public function create(Company $company, array $data): User
    {
        return User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::User,
            'company_id' => $company->id,
            'department_id' => $data['department_id'],
            'birth_date' => $data['birth_date'],
            'hire_date' => $data['hire_date'],
            'gender' => $data['gender'],
        ]);
    }

    /**
     * @param  array{name: string, email: string, password?: string|null, birth_date: string, hire_date: string, gender: string, department_id: int}  $data
     */
    public function update(User $user, array $data): User
    {
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->birth_date = $data['birth_date'];
        $user->hire_date = $data['hire_date'];
        $user->gender = $data['gender'];
        $user->department_id = $data['department_id'];

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return $user;
    }

    public function deactivate(User $user): void
    {
        $user->update(['deactivated_at' => now()]);
    }

    public function activate(User $user): void
    {
        $user->update(['deactivated_at' => null]);
    }
}

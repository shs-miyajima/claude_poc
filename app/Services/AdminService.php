<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminService
{
    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function create(Company $company, array $data): User
    {
        return User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::Admin,
            'company_id' => $company->id,
        ]);
    }

    /**
     * @param  array{name: string, email: string, password?: string|null}  $data
     */
    public function update(User $admin, array $data): User
    {
        $admin->name = $data['name'];
        $admin->email = $data['email'];

        if (! empty($data['password'])) {
            $admin->password = Hash::make($data['password']);
        }

        $admin->save();

        return $admin;
    }

    public function deactivate(User $admin): void
    {
        $admin->update(['deactivated_at' => now()]);
    }

    public function activate(User $admin): void
    {
        $admin->update(['deactivated_at' => null]);
    }
}

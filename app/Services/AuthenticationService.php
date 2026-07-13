<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthenticationService
{
    public function attempt(?string $companyCode, string $email, string $password): ?User
    {
        if ($companyCode === null || $companyCode === '') {
            $query = User::query()->where('role', UserRole::SuperUser->value);
        } else {
            $company = Company::query()->active()->where('code', $companyCode)->first();

            if ($company === null) {
                return null;
            }

            $query = User::query()->where('company_id', $company->id);
        }

        $user = $query->active()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            return null;
        }

        Auth::login($user);

        return $user;
    }
}

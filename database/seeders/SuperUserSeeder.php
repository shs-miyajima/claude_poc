<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperUserSeeder extends Seeder
{
    /**
     * Seed the initial super user.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => env('SUPER_USER_EMAIL', 'super@example.com')],
            [
                'name' => 'スーパーユーザー',
                'password' => Hash::make(env('SUPER_USER_PASSWORD', 'super1234')),
                'role' => UserRole::SuperUser,
                'company_id' => null,
            ],
        );
    }
}

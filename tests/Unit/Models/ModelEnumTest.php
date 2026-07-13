<?php

namespace Tests\Unit\Models;

use App\Enums\Gender;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelEnumTest extends TestCase
{
    use RefreshDatabase;

    /**
     * PU-115-other: UserRole enum の value がロールごとに一致する
     */
    public function test_user_role_enum_values(): void
    {
        $this->assertSame('super_user', UserRole::SuperUser->value);
        $this->assertSame('admin', UserRole::Admin->value);
        $this->assertSame('user', UserRole::User->value);
    }

    /**
     * PU-116-other: Gender::label() が日本語ラベルを返す
     */
    public function test_gender_label(): void
    {
        $this->assertSame('男性', Gender::Male->label());
        $this->assertSame('女性', Gender::Female->label());
        $this->assertSame('その他', Gender::Other->label());
    }

    /**
     * PU-117-other: Company::scopeActive が無効化済み企業を除外する
     */
    public function test_company_scope_active_excludes_deactivated(): void
    {
        $active = Company::factory()->create();
        $deactivated = Company::factory()->deactivated()->create();

        $result = Company::query()->active()->get();

        $this->assertTrue($result->contains($active));
        $this->assertFalse($result->contains($deactivated));
    }

    /**
     * PU-118-other: User のロール判定メソッドが role に応じて true を返す
     */
    public function test_user_role_check_methods(): void
    {
        $superUser = User::factory()->superUser()->make();
        $admin = User::factory()->admin()->make();
        $user = User::factory()->make();

        $this->assertTrue($superUser->isSuperUser());
        $this->assertFalse($superUser->isAdmin());
        $this->assertFalse($superUser->isUser());

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isSuperUser());
        $this->assertFalse($admin->isUser());

        $this->assertTrue($user->isUser());
        $this->assertFalse($user->isSuperUser());
        $this->assertFalse($user->isAdmin());
    }
}

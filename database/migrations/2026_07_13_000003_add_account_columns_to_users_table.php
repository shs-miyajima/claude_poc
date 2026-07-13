<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_unique');

            $table->string('role', 20)->after('id');
            $table->foreignId('company_id')->nullable()->after('role')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->after('company_id')->constrained()->restrictOnDelete();
            $table->date('birth_date')->nullable()->after('password');
            $table->date('hire_date')->nullable()->after('birth_date');
            $table->string('gender', 10)->nullable()->after('hire_date');
            $table->timestamp('deactivated_at')->nullable()->after('gender');
        });

        DB::statement('CREATE UNIQUE INDEX users_company_email_unique ON users (company_id, email) NULLS NOT DISTINCT');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_company_email_unique');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn([
                'role',
                'company_id',
                'department_id',
                'birth_date',
                'hire_date',
                'gender',
                'deactivated_at',
            ]);

            $table->unique('email');
        });
    }
};

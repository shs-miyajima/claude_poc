<?php

namespace App\Http\Controllers\Company;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminStoreRequest;
use App\Http\Requests\AdminUpdateRequest;
use App\Models\User;
use App\Services\AdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function __construct(private readonly AdminService $adminService) {}

    public function index(): View
    {
        $admins = User::query()
            ->where('company_id', app('currentCompany')->id)
            ->where('role', UserRole::Admin->value)
            ->orderBy('id')
            ->paginate(20);

        return view('company.admins.index', ['admins' => $admins]);
    }

    public function create(): View
    {
        return view('company.admins.create');
    }

    public function store(AdminStoreRequest $request): RedirectResponse
    {
        $this->adminService->create(app('currentCompany'), $request->validated());

        return redirect()->route('company.admins.index');
    }

    public function edit(User $admin): View
    {
        $this->ensureAccessible($admin);

        return view('company.admins.edit', ['admin' => $admin]);
    }

    public function update(AdminUpdateRequest $request, User $admin): RedirectResponse
    {
        $this->ensureAccessible($admin);

        $this->adminService->update($admin, $request->validated());

        return redirect()->route('company.admins.index');
    }

    public function deactivate(User $admin): RedirectResponse
    {
        $this->ensureCompanyMatch($admin);

        $this->adminService->deactivate($admin);

        return redirect()->route('company.admins.index');
    }

    public function activate(User $admin): RedirectResponse
    {
        $this->ensureCompanyMatch($admin);

        $this->adminService->activate($admin);

        return redirect()->route('company.admins.index');
    }

    private function ensureAccessible(User $admin): void
    {
        $this->ensureCompanyMatch($admin);

        if (! $admin->isActive()) {
            abort(403);
        }
    }

    private function ensureCompanyMatch(User $admin): void
    {
        if ($admin->company_id !== app('currentCompany')->id) {
            abort(403);
        }
    }
}

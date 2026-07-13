<?php

namespace App\Http\Controllers\Company;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\Department;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function index(): View
    {
        $users = User::query()
            ->where('company_id', app('currentCompany')->id)
            ->where('role', UserRole::User->value)
            ->orderBy('id')
            ->paginate(20);

        return view('company.users.index', ['users' => $users]);
    }

    public function create(): View
    {
        $departments = Department::query()
            ->where('company_id', app('currentCompany')->id)
            ->active()
            ->orderBy('id')
            ->get();

        return view('company.users.create', ['departments' => $departments]);
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $this->userService->create(app('currentCompany'), $request->validated());

        return redirect()->route('company.users.index');
    }

    public function edit(User $user): View
    {
        $this->ensureAccessible($user);

        $companyId = app('currentCompany')->id;

        $departments = Department::query()
            ->where('company_id', $companyId)
            ->where(fn ($query) => $query->whereNull('deactivated_at')->orWhere('id', $user->department_id))
            ->orderBy('id')
            ->get();

        return view('company.users.edit', ['user' => $user, 'departments' => $departments]);
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $this->ensureAccessible($user);

        $this->userService->update($user, $request->validated());

        return redirect()->route('company.users.index');
    }

    public function deactivate(User $user): RedirectResponse
    {
        $this->ensureCompanyMatch($user);

        $this->userService->deactivate($user);

        return redirect()->route('company.users.index');
    }

    public function activate(User $user): RedirectResponse
    {
        $this->ensureCompanyMatch($user);

        $this->userService->activate($user);

        return redirect()->route('company.users.index');
    }

    private function ensureAccessible(User $user): void
    {
        $this->ensureCompanyMatch($user);

        if (! $user->isActive()) {
            abort(403);
        }
    }

    private function ensureCompanyMatch(User $user): void
    {
        if ($user->company_id !== app('currentCompany')->id) {
            abort(403);
        }
    }
}

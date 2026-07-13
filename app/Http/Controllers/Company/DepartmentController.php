<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepartmentStoreRequest;
use App\Http\Requests\DepartmentUpdateRequest;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function __construct(private readonly DepartmentService $departmentService) {}

    public function index(): View
    {
        $departments = Department::query()
            ->where('company_id', app('currentCompany')->id)
            ->orderBy('id')
            ->paginate(20);

        return view('company.departments.index', ['departments' => $departments]);
    }

    public function create(): View
    {
        return view('company.departments.create');
    }

    public function store(DepartmentStoreRequest $request): RedirectResponse
    {
        $this->departmentService->create(app('currentCompany'), $request->validated());

        return redirect()->route('company.departments.index');
    }

    public function edit(Department $department): View
    {
        $this->ensureAccessible($department);

        return view('company.departments.edit', ['department' => $department]);
    }

    public function update(DepartmentUpdateRequest $request, Department $department): RedirectResponse
    {
        $this->ensureAccessible($department);

        $this->departmentService->update($department, $request->validated());

        return redirect()->route('company.departments.index');
    }

    public function deactivate(Department $department): RedirectResponse
    {
        $this->ensureCompanyMatch($department);

        $this->departmentService->deactivate($department);

        return redirect()->route('company.departments.index');
    }

    public function activate(Department $department): RedirectResponse
    {
        $this->ensureCompanyMatch($department);

        $this->departmentService->activate($department);

        return redirect()->route('company.departments.index');
    }

    private function ensureAccessible(Department $department): void
    {
        $this->ensureCompanyMatch($department);

        if (! $department->isActive()) {
            abort(403);
        }
    }

    private function ensureCompanyMatch(Department $department): void
    {
        if ($department->company_id !== app('currentCompany')->id) {
            abort(403);
        }
    }
}

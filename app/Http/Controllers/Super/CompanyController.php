<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyStoreRequest;
use App\Http\Requests\CompanyUpdateRequest;
use App\Models\Company;
use App\Services\CompanyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function __construct(private readonly CompanyService $companyService) {}

    public function index(): View
    {
        $companies = Company::query()->orderBy('id')->paginate(20);

        return view('super.companies.index', ['companies' => $companies]);
    }

    public function create(): View
    {
        return view('super.companies.create');
    }

    public function store(CompanyStoreRequest $request): RedirectResponse
    {
        $this->companyService->create($request->validated());

        return redirect()->route('super.companies.index');
    }

    public function edit(Company $company): View
    {
        if (! $company->isActive()) {
            abort(403);
        }

        return view('super.companies.edit', ['company' => $company]);
    }

    public function update(CompanyUpdateRequest $request, Company $company): RedirectResponse
    {
        if (! $company->isActive()) {
            abort(403);
        }

        $this->companyService->update($company, $request->validated());

        return redirect()->route('super.companies.index');
    }

    public function deactivate(Company $company): RedirectResponse
    {
        $this->companyService->deactivate($company);

        return redirect()->route('super.companies.index');
    }

    public function activate(Company $company): RedirectResponse
    {
        $this->companyService->activate($company);

        return redirect()->route('super.companies.index');
    }
}

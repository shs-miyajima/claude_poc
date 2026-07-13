<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompanySwitchController extends Controller
{
    public function enter(Request $request, Company $company): RedirectResponse
    {
        if (! $company->isActive()) {
            abort(403);
        }

        $request->session()->put('acting_company_id', $company->id);

        return redirect()->route('company.home');
    }

    public function exit(Request $request): RedirectResponse
    {
        $request->session()->forget('acting_company_id');

        return redirect()->route('super.companies.index');
    }
}

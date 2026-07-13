<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCompanyContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $company = $user->company;
        } elseif ($user->isSuperUser()) {
            $companyId = $request->session()->get('acting_company_id');
            $company = $companyId !== null ? Company::query()->find($companyId) : null;
        } else {
            $company = null;
        }

        if ($company === null || ! $company->isActive()) {
            abort(403);
        }

        app()->instance('currentCompany', $company);

        return $next($request);
    }
}

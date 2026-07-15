<?php

namespace App\Http\Controllers\Company;

use App\Enums\SurveyStatus;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $company = app('currentCompany');

        return view('company.home', [
            'company' => $company,
            'publishedSurveyCount' => $company->surveys()->where('status', SurveyStatus::Published)->count(),
            'draftSurveyCount' => $company->surveys()->where('status', SurveyStatus::Draft)->count(),
            'targetUserCount' => $company->users()->active()->count(),
        ]);
    }
}

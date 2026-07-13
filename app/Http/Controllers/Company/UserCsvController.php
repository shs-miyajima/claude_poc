<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserCsvImportRequest;
use App\Services\UserCsvImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class UserCsvController extends Controller
{
    public function __construct(private readonly UserCsvImportService $userCsvImportService) {}

    public function show(): View
    {
        return view('company.users.csv');
    }

    public function store(UserCsvImportRequest $request): RedirectResponse|Response
    {
        $result = $this->userCsvImportService->import(app('currentCompany'), $request->file('csv_file'));

        if (! $result->succeeded()) {
            return response()->view('company.users.csv', ['csvErrors' => $result->errors], 422);
        }

        return redirect()->route('company.users.index')->with('status', "{$result->successCount}件登録しました");
    }
}

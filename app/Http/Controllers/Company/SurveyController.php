<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\SurveyStoreRequest;
use App\Http\Requests\SurveyUpdateRequest;
use App\Models\Survey;
use App\Services\SurveyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SurveyController extends Controller
{
    public function __construct(private readonly SurveyService $surveyService) {}

    public function index(): View
    {
        $surveys = Survey::query()
            ->where('company_id', app('currentCompany')->id)
            ->withCount('questions')
            ->orderByDesc('id')
            ->paginate(20);

        return view('company.surveys.index', ['surveys' => $surveys]);
    }

    public function create(): View
    {
        return view('company.surveys.create');
    }

    public function store(SurveyStoreRequest $request): RedirectResponse
    {
        $this->surveyService->create(app('currentCompany'), auth()->user(), $request->validated());

        return redirect()->route('company.surveys.index');
    }

    public function show(Survey $survey): View
    {
        $this->ensureCompanyMatch($survey);

        return view('company.surveys.show', ['survey' => $survey]);
    }

    public function edit(Survey $survey): View
    {
        $this->ensureEditable($survey);

        return view('company.surveys.edit', ['survey' => $survey]);
    }

    public function update(SurveyUpdateRequest $request, Survey $survey): RedirectResponse
    {
        $this->ensureEditable($survey);

        $this->surveyService->update($survey, $request->validated());

        return redirect()->route('company.surveys.index');
    }

    public function destroy(Survey $survey): RedirectResponse
    {
        $this->ensureEditable($survey);

        $this->surveyService->delete($survey);

        return redirect()->route('company.surveys.index');
    }

    public function publish(Survey $survey): RedirectResponse
    {
        $this->ensureEditable($survey);

        $this->surveyService->publish($survey);

        return redirect()->route('company.surveys.index');
    }

    private function ensureEditable(Survey $survey): void
    {
        $this->ensureCompanyMatch($survey);

        if (! $survey->isDraft()) {
            abort(403);
        }
    }

    private function ensureCompanyMatch(Survey $survey): void
    {
        if ($survey->company_id !== app('currentCompany')->id) {
            abort(403);
        }
    }
}

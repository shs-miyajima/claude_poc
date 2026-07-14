<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\SurveyRequestRules;
use Illuminate\Foundation\Http\FormRequest;

class SurveyUpdateRequest extends FormRequest
{
    use SurveyRequestRules;

    public function authorize(): bool
    {
        return true;
    }
}

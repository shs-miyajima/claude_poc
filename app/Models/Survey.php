<?php

namespace App\Models;

use App\Enums\AnswerVisibility;
use App\Enums\SurveyStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'created_by',
        'title',
        'answer_start_date',
        'answer_end_date',
        'answer_visibility',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'answer_start_date' => 'date',
            'answer_end_date' => 'date',
            'answer_visibility' => AnswerVisibility::class,
            'status' => SurveyStatus::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('sort_order');
    }

    public function isDraft(): bool
    {
        return $this->status === SurveyStatus::Draft;
    }

    public function isPublished(): bool
    {
        return $this->status === SurveyStatus::Published;
    }
}

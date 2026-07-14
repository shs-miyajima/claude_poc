<?php

namespace App\Models;

use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    public const ANSWER_MAX_LENGTH = 2000;

    protected $fillable = [
        'survey_id',
        'question_type',
        'body',
        'is_required',
        'sort_order',
        'scale_min_label',
        'scale_max_label',
    ];

    protected function casts(): array
    {
        return [
            'question_type' => QuestionType::class,
            'is_required' => 'bool',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function choices(): HasMany
    {
        return $this->hasMany(Choice::class)->orderBy('sort_order');
    }

    public function hasChoices(): bool
    {
        return $this->question_type->hasChoices();
    }
}

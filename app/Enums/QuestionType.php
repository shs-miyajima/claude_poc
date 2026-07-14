<?php

namespace App\Enums;

enum QuestionType: string
{
    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case FreeText = 'free_text';
    case Scale = 'scale';

    public function label(): string
    {
        return match ($this) {
            self::SingleChoice => '単一選択',
            self::MultipleChoice => '複数選択',
            self::FreeText => '自由記述',
            self::Scale => '段階評価',
        };
    }

    public function hasChoices(): bool
    {
        return $this === self::SingleChoice || $this === self::MultipleChoice;
    }
}

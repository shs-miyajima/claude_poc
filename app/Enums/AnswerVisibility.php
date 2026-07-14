<?php

namespace App\Enums;

enum AnswerVisibility: string
{
    case Named = 'named';
    case Anonymous = 'anonymous';

    public function label(): string
    {
        return match ($this) {
            self::Named => '記名',
            self::Anonymous => '匿名',
        };
    }
}

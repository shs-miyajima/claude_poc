<?php

namespace App\Services;

class CsvImportResult
{
    /**
     * @param  array<int, array{line: int, message: string}>  $errors
     */
    public function __construct(
        public readonly int $successCount,
        public readonly array $errors,
    ) {}

    public function succeeded(): bool
    {
        return $this->errors === [];
    }
}

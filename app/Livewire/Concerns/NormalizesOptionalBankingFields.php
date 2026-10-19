<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

/**
 * Livewire deliberately skips ConvertEmptyStringsToNull for component updates,
 * so a blank banking input arrives as '' (not null). '' fails `numeric` and
 * `unique` validation and would persist as an empty string instead of NULL.
 */
trait NormalizesOptionalBankingFields
{
    protected function normalizeOptionalBankingFields(): void
    {
        $this->account_bank = $this->blankToNull($this->account_bank);
        $this->account_number = $this->blankToNull($this->account_number);
        $this->ssnit_number = $this->blankToNull($this->ssnit_number);
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

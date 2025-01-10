<?php

namespace App\Rules\CsvTransactionImport;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;

class MoneyColumnRule implements ValidationRule
{
    public function __construct(private Collection $column) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        foreach ($this->column as $entry) {

            if (! is_numeric(str_replace(',', '.', $entry))) {
                $fail(__('konto.csv-verify-money-error', ['value' => $entry]));
            }
        }
    }
}

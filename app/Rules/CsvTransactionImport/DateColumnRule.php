<?php

namespace App\Rules\CsvTransactionImport;

use Carbon\Exceptions\InvalidFormatException;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;

class DateColumnRule implements ValidationRule
{
    public function __construct(private readonly Collection $column) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $firstDate = guessCarbon($this->column->first());
            $lastDate = guessCarbon($this->column->last());
            if (! $firstDate->lessThanOrEqualTo($lastDate)) {
                $fail(__('konto.csv-verify-date-order-error'));
            }
            // dump("$attribute test if $firstDate >= $lastDate -> Result: ". !$firstDate->lessThanOrEqualTo($lastDate) );
        } catch (InvalidFormatException $exception) {
            $fail(__('konto.csv-verify-date-error'));
        }
    }
}

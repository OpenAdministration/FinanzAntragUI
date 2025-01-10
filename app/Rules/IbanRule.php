<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IbanRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! verify_iban($value)) {
            $fail(__('konto.csv-verify-iban-error'));
        }
    }
}

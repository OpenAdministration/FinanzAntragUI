<?php

namespace App\Rules\CsvTransactionImport;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Translation\PotentiallyTranslatedString;
use Intervention\Validation\Rules\Bic;

class BicColumnRule implements ValidationRule
{
    public function __construct(public Collection $bics) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach ($this->bics as $bic) {
            if (empty($bic)) {
                continue;
            }

            $v = Validator::make(['bic' => $bic], ['bic' => new Bic]);

            if ($v->fails()) {
                $fail(__('konto.csv-verify-iban-error'));
            }
        }
    }
}

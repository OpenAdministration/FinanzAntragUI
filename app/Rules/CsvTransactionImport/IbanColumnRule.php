<?php

namespace App\Rules\CsvTransactionImport;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Translation\PotentiallyTranslatedString;
use Intervention\Validation\Rules\Iban;

class IbanColumnRule implements ValidationRule
{
    public function __construct(public Collection $ibans) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach ($this->ibans as $iban) {
            if (empty($iban)) {
                continue;
            }
            $v = Validator::make(['iban' => $iban], ['iban' => new Iban]);
            if ($v->fails()) {
                $fail(__('konto.csv-verify-iban-error'));
            }
        }
    }
}

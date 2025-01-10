<?php

namespace Database\Factories\Legacy;

use App\Models\Legacy\BankAccount;
use App\Models\Legacy\BankTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankTransactionFactory extends Factory
{
    protected $model = BankTransaction::class;

    public function definition(): array
    {
        $date = fake()->date();

        return [
            'date' => $date,
            'valuta' => $date,
            'type' => fake()->word(),
            'empf_iban' => fake()->iban('de'),
            'empf_bic' => fake()->swiftBicNumber(),
            'empf_name' => fake()->name(),
            'primanota' => fake()->randomDigit(),
            'zweck' => fake()->word(),
            'comment' => fake()->word(),
            'customer_ref' => fake()->word(),
            'konto_id' => BankAccount::factory(),

            'value' => (fake()->boolean() ? '' : '-').fake()->randomFloat(2),
            'saldo' => fake()->randomFloat(2),
        ];
    }

    public function continuous(int $amount, Carbon $minDate, null|string|float $startValue = null): self
    {
        $lastSaldo = $startValue ?? fake()->randomFloat(2);
        $lastDate = $minDate;
        $seq = [];
        $id = 1;

        for ($i = 0; $i < $amount; $i++) {
            $val = (fake()->boolean() ? '' : '-').fake()->randomFloat(2, 100, 100000);
            $lastSaldo = bcadd($lastSaldo, $val, 2);
            $minutes = fake()->randomNumber(4);
            $lastDate = $lastDate->addMinutes($minutes);
            $seq[] = [
                'id' => $id++,
                'date' => $lastDate->format('Y-m-d'),
                'valuta' => $lastDate->format('Y-m-d'),
                'value' => $val,
                'saldo' => $lastSaldo,
            ];
        }

        return $this->sequence(...$seq)->count($amount);
    }
}

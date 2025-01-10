<?php

namespace Database\Factories\Legacy;

use App\Models\Legacy\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'short' => fake()->randomLetter(),
            // 'sync_from' => fake()->date(),
            // 'sync_until' => fake()->date(),
            'iban' => fake()->iban('de'),
            // 'last_sync' => fake()->date(),
        ];
    }
}

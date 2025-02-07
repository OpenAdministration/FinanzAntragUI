<?php

namespace Database\Factories;

use App\Models\FiscalYear;
use Illuminate\Database\Eloquent\Factories\Factory;

class FiscalYearFactory extends Factory
{
    public function definition(): array
    {
        return [
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->date(),
        ];
    }
}

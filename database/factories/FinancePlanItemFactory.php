<?php

namespace Database\Factories;

use App\Models\FinancePlanItem;
use App\Models\FinancePlanTopic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancePlanItem>
 */
class FinancePlanItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'topic_id' => FinancePlanTopic::factory(),
            'name' => fake()->name(),
            'value' => fake()->randomFloat(2, 1, 100),
            'amount' => fake()->numberBetween(1, 10),
            'total' => fake()->randomFloat(2, 10, 1000),
            'description' => fake()->text(),
        ];
    }
}

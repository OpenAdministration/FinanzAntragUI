<?php

namespace Database\Factories;

use App\Models\Enums\BudgetType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BudgetItem>
 */
class BudgetItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(asText: true),
            'short_name' => fake()->unique()->buildingNumber(),
            'value' => fake()->numberBetween(1, 50) * 1000,
            'budget_type' => fake()->randomElement(BudgetType::cases()),
            'description' => fake()->text(),
        ];
    }
}

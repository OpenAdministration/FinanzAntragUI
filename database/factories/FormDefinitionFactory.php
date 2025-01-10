<?php

namespace Database\Factories;

use App\Models\FormDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormDefinition>
 */
class FormDefinitionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // 'type' => fake()->randomElement(['project', 'application']),
            'name' => fake()->city(),
            'version' => fake()->numberBetween(1, 10),
            'title' => fake()->word(),
            'description' => fake()->sentence(),
            'active' => fake()->boolean(),
        ];
    }

    public function forProject(): static
    {
        return $this->state(['type' => 'project']);
    }

    public function forApplication(): static
    {
        return $this->state(['type' => 'application']);
    }
}

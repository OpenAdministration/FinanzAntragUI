<?php

namespace Database\Factories;

use App\Models\FormDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FormField>
 */
class FormFieldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_definition_id' => FormDefinition::factory(),
            'name' => fake()->unique()->word(),
            'label' => fake()->unique()->word(),
            'type' => fake()->randomElement(['date', 'textarea', 'number']),
            'default_value' => '',
            'position' => fake()->unique()->numberBetween(1, 100),
            'view_key' => '',
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\FormField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FormFieldValidation>
 */
class FormFieldValidationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_field_id' => FormField::factory(),
            'validation_rule' => $this->faker->randomElement(['max', 'min']),
            'validation_parameter' => $this->faker->numberBetween(10, 255)
        ];
    }
}

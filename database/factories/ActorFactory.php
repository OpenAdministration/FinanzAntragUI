<?php

namespace Database\Factories;

use App\Models\Actor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Actor>
 */
class ActorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'is_organisation' => false,
            'name' => fake()->name(),
            'street' => fake()->streetAddress(),
            'zip_code' => fake()->postcode(),
            'city' => fake()->city(),
            'iban' => fake()->iban(),
            'bic' => fake()->swiftBicNumber(),
        ];
    }

    public function asOrganisation(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_organisation' => true,
                'name' => fake()->company(),
                'website' => fake()->url(),
                'register_number' => fake()->creditCardNumber(), // next best to fake
                'vat_deduction' => fake()->boolean(),
                'charity' => fake()->boolean(),
            ];
        });
    }
}

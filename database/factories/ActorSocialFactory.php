<?php

namespace Database\Factories;

use App\Models\ActorSocial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActorSocial>
 */
class ActorSocialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => fake()->randomElement(['facebook', 'instagram', 'twitter']),
            'url' => fake()->url(),
        ];
    }
}

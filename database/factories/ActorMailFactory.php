<?php

namespace Database\Factories;

use App\Models\Actor;
use App\Models\ActorMail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActorMail>
 */
class ActorMailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_id' => Actor::factory(),
            'value' => fake()->email(),
        ];
    }
}

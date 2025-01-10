<?php

namespace Database\Factories\Legacy;

use App\Models\Legacy\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Model>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'createdat' => fake()->dateTime(),
            'lastupdated' => fake()->dateTime(),
            'version' => 1,
            'state' => 'draft',
            'name' => fake()->text(30),
            'responsible' => fake()->userName(),
            'org' => fake()->company(),
            'org-mail' => fake()->companyEmail(),
            'protokoll' => fake()->url(),
            'recht' => 'stura',
            'recht-additional' => fake()->text(10),
            'date-start' => fake()->date(),
            'date-end' => fake()->date(),
            'beschreibung' => fake()->text(500),
        ];
    }

    public function by(User $user): ProjectFactory|Factory
    {
        return $this->state(fn (array $attributes) => [
            'creator_id' => $user->id,
            'stateCreator_id' => $user->id,
        ]);
    }

    public function projectState(string $state): ProjectFactory|Factory
    {
        return $this->state(fn (array $attributes) => [
            'state' => $state,
        ]);
    }
}

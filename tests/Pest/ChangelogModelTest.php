<?php

use App\Models\Changelog;
use App\Models\Legacy\Project;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('json casting is working', function () {
    $user = User::factory()->create();
    $cl = new Changelog([
        'user_id' => $user->id,
        'type' => 'projekt',
        'type_id' => '1',
        'previous_data' => ['test' => 'test'],
    ]);

    expect($cl->save())->toBeTrue();
});

test('Event dispatching from legacy project model update', function () {
    Event::fake();
    $user = User::factory()->create();
    $project = Project::factory()->by($user)->create();

    Event::assertNotDispatched(\App\Events\UpdatingModel::class);

    $project->name = fake()->sentence();
    $project->save();

    Event::assertDispatched(\App\Events\UpdatingModel::class, function ($event) use ($project) {
        return $event->model->id === $project->id && $event->model instanceof Project;
    });
});

test('dispatched event gets logged at legacy project model update', function () {
    $user = User::factory()->create();
    actingAs($user);
    $project = Project::factory()->by($user)->create();
    $old_name = $project->name;
    $project->name = fake()->sentence();
    $project->save();
    // check if Logging works
    $log = Changelog::where('type', '=', Project::class)
        ->where('type_id', '=', $project->id)->first();

    expect($log)->not()->toBeNull()
        ->and($log->type)->toBe(Project::class)
        ->and($log->type_id)->toBe($project->id)
        ->and($log->user_id)->toBe($user->id)
        ->and($log->previous_data)->toBe(['name' => $old_name]);
});

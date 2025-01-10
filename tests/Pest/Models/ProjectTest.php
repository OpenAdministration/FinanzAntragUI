<?php

use App\Models\Application;
use App\Models\FinancePlanItem;
use App\Models\FinancePlanTopic;
use App\Models\Project;

test('project factory', function () {
    $project = Project::factory()->has(
        Application::factory()->count(2)->has(
            FinancePlanTopic::factory()->count(3)->has(
                FinancePlanItem::factory()->count(3)
            )
        )
    )->create();
});

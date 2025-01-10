<?php

use App\Models\StudentBodyDuty;

use function Pest\Laravel\assertModelExists;

test('student body duties factory', function () {
    $duties = StudentBodyDuty::factory(5)->create();
    foreach ($duties as $duty) {
        assertModelExists($duty);
    }
});

<?php

use App\Models\LegalBasis;

use function Pest\Laravel\assertModelExists;

test('legal basis', function () {
    $legal = LegalBasis::factory()->create();
    assertModelExists($legal);
});

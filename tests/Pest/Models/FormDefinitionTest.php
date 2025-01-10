<?php

use App\Models\FormDefinition;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\FormFieldValidation;

use function Pest\Laravel\assertModelExists;

test('form definition factory', function () {
    $def = FormDefinition::factory()->has(
        FormField::factory(5)->has(
            FormFieldOption::factory()->count(5)
        )->has(
            FormFieldValidation::factory()->count(5)
        )
    )->create();

    assertModelExists($def);
    expect($def->formFields->count())->toBe(5);

});

<?php

namespace App\Models;

use Database\Factories\FormFieldValidationFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\FormFieldValidation
 *
 * @property int $id
 * @property int $form_field_id
 * @property string $validation_rule
 * @property string $validation_parameter
 *
 * @method static FormFieldValidationFactory factory($count = null, $state = [])
 * @method static Builder|FormFieldValidation newModelQuery()
 * @method static Builder|FormFieldValidation newQuery()
 * @method static Builder|FormFieldValidation query()
 * @method static Builder|FormFieldValidation whereFormFieldId($value)
 * @method static Builder|FormFieldValidation whereId($value)
 * @method static Builder|FormFieldValidation whereValidationParameter($value)
 * @method static Builder|FormFieldValidation whereValidationRule($value)
 *
 * @property-read FormField|null $field
 *
 * @mixin Eloquent
 */
class FormFieldValidation extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class);
    }
}

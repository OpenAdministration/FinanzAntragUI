<?php

namespace App\Models;

use Database\Factories\FormFieldFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\FormField
 *
 * @property int $id
 * @property int $form_definition_id
 * @property string $name
 * @property string $label
 * @property string $type
 * @property string|null $default_value
 * @property int $position
 * @property string $view_key
 *
 * @method static FormFieldFactory factory($count = null, $state = [])
 * @method static Builder|FormField newModelQuery()
 * @method static Builder|FormField newQuery()
 * @method static Builder|FormField query()
 * @method static Builder|FormField whereDefaultValue($value)
 * @method static Builder|FormField whereFormDefinitionId($value)
 * @method static Builder|FormField whereId($value)
 * @method static Builder|FormField whereLabel($value)
 * @method static Builder|FormField whereName($value)
 * @method static Builder|FormField wherePosition($value)
 * @method static Builder|FormField whereType($value)
 * @method static Builder|FormField whereViewKey($value)
 *
 * @property-read FormDefinition|null $definition
 * @property-read Collection<int, FormFieldOption> $options
 * @property-read int|null $options_count
 * @property-read Collection<int, FormFieldValidation> $validations
 * @property-read int|null $validations_count
 *
 * @mixin Eloquent
 */
class FormField extends Model
{
    use HasFactory;

    public function formDefinition(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(FormFieldOption::class);
    }

    public function formFieldOptions(): HasMany
    {
        return $this->options();
    }

    public function validations(): HasMany
    {
        return $this->hasMany(FormFieldValidation::class);
    }

    public function formFieldValidations(): HasMany
    {
        return $this->validations();
    }
}

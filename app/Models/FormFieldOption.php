<?php

namespace App\Models;

use Database\Factories\FormFieldOptionFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\FormFieldOption
 *
 * @property int $id
 * @property int $form_field_id
 * @property string $text
 * @property string $subtext
 * @property int $position
 *
 * @method static FormFieldOptionFactory factory($count = null, $state = [])
 * @method static Builder|FormFieldOption newModelQuery()
 * @method static Builder|FormFieldOption newQuery()
 * @method static Builder|FormFieldOption query()
 * @method static Builder|FormFieldOption whereFormFieldId($value)
 * @method static Builder|FormFieldOption whereId($value)
 * @method static Builder|FormFieldOption wherePosition($value)
 * @method static Builder|FormFieldOption whereSubtext($value)
 * @method static Builder|FormFieldOption whereText($value)
 *
 * @property-read FormField|null $field
 *
 * @mixin Eloquent
 */
class FormFieldOption extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class);
    }
}

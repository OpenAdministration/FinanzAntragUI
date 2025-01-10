<?php

namespace App\Models;

use Database\Factories\FormDefinitionFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\FormDefinition
 *
 * @property int $id
 * @property string $type
 * @property string $name
 * @property string $version
 * @property string $title
 * @property string $description
 * @property int $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static FormDefinitionFactory factory($count = null, $state = [])
 * @method static Builder|FormDefinition newModelQuery()
 * @method static Builder|FormDefinition newQuery()
 * @method static Builder|FormDefinition query()
 * @method static Builder|FormDefinition whereActive($value)
 * @method static Builder|FormDefinition whereCreatedAt($value)
 * @method static Builder|FormDefinition whereDescription($value)
 * @method static Builder|FormDefinition whereId($value)
 * @method static Builder|FormDefinition whereName($value)
 * @method static Builder|FormDefinition whereTitle($value)
 * @method static Builder|FormDefinition whereType($value)
 * @method static Builder|FormDefinition whereUpdatedAt($value)
 * @method static Builder|FormDefinition whereVersion($value)
 *
 * @property-read Collection<int, FormField> $fields
 * @property-read int|null $fields_count
 *
 * @mixin Eloquent
 */
class FormDefinition extends Model
{
    use HasFactory;

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class, 'form_definition_id');
    }

    public function formFields(): HasMany
    {
        return $this->fields();
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}

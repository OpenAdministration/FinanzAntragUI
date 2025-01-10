<?php

namespace App\Models;

use Database\Factories\ChangelogFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\Changelog
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property int $type_id
 * @property array $previous_data
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static ChangelogFactory factory($count = null, $state = [])
 * @method static Builder|Changelog newModelQuery()
 * @method static Builder|Changelog newQuery()
 * @method static Builder|Changelog query()
 * @method static Builder|Changelog whereCreatedAt($value)
 * @method static Builder|Changelog whereId($value)
 * @method static Builder|Changelog wherePreviousData($value)
 * @method static Builder|Changelog whereType($value)
 * @method static Builder|Changelog whereTypeId($value)
 * @method static Builder|Changelog whereUpdatedAt($value)
 * @method static Builder|Changelog whereUserId($value)
 *
 * @mixin Eloquent
 */
class Changelog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'changelog';

    protected $fillable = ['user_id', 'type', 'type_id', 'previous_data'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'previous_data' => 'array',
    ];
}

<?php

namespace App\Models;

use Database\Factories\StudentBodyDutyFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * App\Models\StudentBodyDuty
 *
 * @property int $id
 * @property string $short_key
 * @property string $description_keys
 *
 * @method static StudentBodyDutyFactory factory($count = null, $state = [])
 * @method static Builder|StudentBodyDuty newModelQuery()
 * @method static Builder|StudentBodyDuty newQuery()
 * @method static Builder|StudentBodyDuty query()
 * @method static Builder|StudentBodyDuty whereDescriptionKeys($value)
 * @method static Builder|StudentBodyDuty whereId($value)
 * @method static Builder|StudentBodyDuty whereShortKey($value)
 *
 * @mixin Eloquent
 */
class StudentBodyDuty extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'projects_to_student_body_duties');
    }
}

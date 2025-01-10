<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * App\Models\Project
 *
 * @property int $id
 * @property int $version
 * @property string $state
 * @property int $user_id
 * @property string $name
 * @property string $start_date
 * @property string $end_date
 * @property string $description
 * @property mixed $extra_fields
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static ProjectFactory factory($count = null, $state = [])
 * @method static Builder|Project newModelQuery()
 * @method static Builder|Project newQuery()
 * @method static Builder|Project query()
 * @method static Builder|Project whereCreatedAt($value)
 * @method static Builder|Project whereDescription($value)
 * @method static Builder|Project whereEndDate($value)
 * @method static Builder|Project whereExtraFields($value)
 * @method static Builder|Project whereId($value)
 * @method static Builder|Project whereName($value)
 * @method static Builder|Project whereStartDate($value)
 * @method static Builder|Project whereState($value)
 * @method static Builder|Project whereUpdatedAt($value)
 * @method static Builder|Project whereUserId($value)
 * @method static Builder|Project whereVersion($value)
 *
 * @mixin Eloquent
 */
class Project extends Model
{
    use HasFactory;

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ProjectAttachment::class);
    }

    public function studentBodyDuties(): HasMany
    {
        return $this->hasMany(StudentBodyDuty::class, 'projects_to_student_body_duties');
    }

    public function formDefinition(): HasOne
    {
        return $this->hasOne(FormDefinition::class);
    }
}

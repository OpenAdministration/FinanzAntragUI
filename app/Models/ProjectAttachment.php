<?php

namespace App\Models;

use Database\Factories\ProjectAttachmentFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\ProjectAttachment
 *
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property string $path
 * @property string $mime_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static ProjectAttachmentFactory factory($count = null, $state = [])
 * @method static Builder|ProjectAttachment newModelQuery()
 * @method static Builder|ProjectAttachment newQuery()
 * @method static Builder|ProjectAttachment query()
 * @method static Builder|ProjectAttachment whereCreatedAt($value)
 * @method static Builder|ProjectAttachment whereId($value)
 * @method static Builder|ProjectAttachment whereMimeType($value)
 * @method static Builder|ProjectAttachment whereName($value)
 * @method static Builder|ProjectAttachment wherePath($value)
 * @method static Builder|ProjectAttachment whereProjectId($value)
 * @method static Builder|ProjectAttachment whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class ProjectAttachment extends Model
{
    use HasFactory;

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}

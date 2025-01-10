<?php

namespace App\Models;

use Database\Factories\ApplicationAttachmentFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\ApplicationAttachment
 *
 * @property int $id
 * @property int $application_id
 * @property string $name
 * @property string $path
 * @property string $mime_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static ApplicationAttachmentFactory factory($count = null, $state = [])
 * @method static Builder|ApplicationAttachment newModelQuery()
 * @method static Builder|ApplicationAttachment newQuery()
 * @method static Builder|ApplicationAttachment query()
 * @method static Builder|ApplicationAttachment whereApplicationId($value)
 * @method static Builder|ApplicationAttachment whereCreatedAt($value)
 * @method static Builder|ApplicationAttachment whereId($value)
 * @method static Builder|ApplicationAttachment whereMimeType($value)
 * @method static Builder|ApplicationAttachment whereName($value)
 * @method static Builder|ApplicationAttachment wherePath($value)
 * @method static Builder|ApplicationAttachment whereUpdatedAt($value)
 *
 * @property-read Application $application
 *
 * @mixin Eloquent
 */
class ApplicationAttachment extends Model
{
    use HasFactory;

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}

<?php

namespace App\Models;

use Database\Factories\ActorMailFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ActorMail
 *
 * @property int $id
 * @property int $actor_id
 * @property string $value
 *
 * @method static ActorMailFactory factory($count = null, $state = [])
 * @method static Builder|ActorMail newModelQuery()
 * @method static Builder|ActorMail newQuery()
 * @method static Builder|ActorMail query()
 * @method static Builder|ActorMail whereActorId($value)
 * @method static Builder|ActorMail whereId($value)
 * @method static Builder|ActorMail whereValue($value)
 *
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property-read Actor|null $actor
 *
 * @method static Builder|ActorMail whereCreatedAt($value)
 * @method static Builder|ActorMail whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class ActorMail extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }
}

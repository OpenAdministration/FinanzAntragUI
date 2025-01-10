<?php

namespace App\Models;

use Database\Factories\ActorPhoneFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ActorPhone
 *
 * @property int $id
 * @property int $actor_id
 * @property string $value
 *
 * @method static ActorPhoneFactory factory($count = null, $state = [])
 * @method static Builder|ActorPhone newModelQuery()
 * @method static Builder|ActorPhone newQuery()
 * @method static Builder|ActorPhone query()
 * @method static Builder|ActorPhone whereActorId($value)
 * @method static Builder|ActorPhone whereId($value)
 * @method static Builder|ActorPhone whereValue($value)
 *
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property-read Actor|null $actor
 *
 * @method static Builder|ActorPhone whereCreatedAt($value)
 * @method static Builder|ActorPhone whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class ActorPhone extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }
}

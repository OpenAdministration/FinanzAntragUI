<?php

namespace App\Models;

use Database\Factories\ActorSocialFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ActorSocial
 *
 * @property int $id
 * @property int $actor_id
 * @property string $provider
 * @property string $url
 *
 * @method static ActorSocialFactory factory($count = null, $state = [])
 * @method static Builder|ActorSocial newModelQuery()
 * @method static Builder|ActorSocial newQuery()
 * @method static Builder|ActorSocial query()
 * @method static Builder|ActorSocial whereActorId($value)
 * @method static Builder|ActorSocial whereId($value)
 * @method static Builder|ActorSocial whereProvider($value)
 * @method static Builder|ActorSocial whereUrl($value)
 *
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property-read Actor|null $actor
 *
 * @method static Builder|ActorSocial whereCreatedAt($value)
 * @method static Builder|ActorSocial whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class ActorSocial extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }
}

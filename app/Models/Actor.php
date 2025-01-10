<?php

namespace App\Models;

use Database\Factories\ActorFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\Actor
 *
 * @property int $id
 * @property int $is_organisation
 * @property string $name
 * @property string $address
 * @property string $iban
 * @property string $bic
 * @property string $website
 * @property string $register_number
 * @property int $vat_deduction
 *
 * @method static ActorFactory factory($count = null, $state = [])
 * @method static Builder|Actor newModelQuery()
 * @method static Builder|Actor newQuery()
 * @method static Builder|Actor query()
 * @method static Builder|Actor whereAddress($value)
 * @method static Builder|Actor whereBic($value)
 * @method static Builder|Actor whereIban($value)
 * @method static Builder|Actor whereId($value)
 * @method static Builder|Actor whereIsOrganisation($value)
 * @method static Builder|Actor whereName($value)
 * @method static Builder|Actor whereRegisterNumber($value)
 * @method static Builder|Actor whereWebsite($value)
 *
 * @property-read Collection<int, ActorMail> $mails
 * @property-read int|null $mails_count
 * @property-read Collection<int, ActorPhone> $phones
 * @property-read int|null $phones_count
 * @property-read Collection<int, ActorSocial> $socials
 * @property-read int|null $socials_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|Actor whereCreatedAt($value)
 * @method static Builder|Actor whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class Actor extends Model
{
    use HasFactory;

    public function scopeOrganisation(Builder $builder): void
    {
        $builder->where('is_organisation', '=', true);
    }

    public function scopeUser(Builder $builder): void
    {
        $builder->where('is_organisation', '=', false);
    }

    public function mails(): HasMany
    {
        return $this->hasMany(ActorMail::class);
    }

    public function actorMails(): HasMany
    {
        return $this->mails();
    }

    public function socials(): HasMany
    {
        return $this->hasMany(ActorSocial::class);
    }

    public function actorSocials(): HasMany
    {
        return $this->socials();
    }

    public function phones(): HasMany
    {
        return $this->hasMany(ActorPhone::class);
    }

    public function actorPhones(): HasMany
    {
        return $this->phones();
    }
}

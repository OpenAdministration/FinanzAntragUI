<?php

namespace App\Models;

use Database\Factories\LegalBasisFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\LegalBasis
 *
 * @property string $uuid
 * @property int $has_details
 * @property int $active
 *
 * @method static LegalBasisFactory factory($count = null, $state = [])
 * @method static Builder|LegalBasis newModelQuery()
 * @method static Builder|LegalBasis newQuery()
 * @method static Builder|LegalBasis query()
 * @method static Builder|LegalBasis whereActive($value)
 * @method static Builder|LegalBasis whereHasDetails($value)
 * @method static Builder|LegalBasis whereUuid($value)
 *
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|LegalBasis whereCreatedAt($value)
 * @method static Builder|LegalBasis whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class LegalBasis extends Model
{
    use HasFactory;

    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class);
    }
}

<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Legacy\ProjectPost
 *
 * @property int $id
 * @property int $projekt_id
 * @property int $titel_id
 * @property float $einnahmen
 * @property float $ausgaben
 * @property string $name
 * @property string $bemerkung
 * @property Project $projekte
 * @property-read \App\Models\Legacy\Project $project
 *
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPost newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPost newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPost query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPost whereAusgaben($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPost whereBemerkung($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPost whereEinnahmen($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPost whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPost whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPost whereProjektId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPost whereTitelId($value)
 *
 * @mixin \Eloquent
 */
class ProjectPost extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'projektposten';

    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['titel_id', 'einnahmen', 'ausgaben', 'name', 'bemerkung'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'projekt_id');
    }
}

<?php

namespace App\Models\Legacy;

use App\Events\UpdatingModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Legacy\Project
 *
 * @property int $id
 * @property int $creator_id
 * @property string $createdat
 * @property string $lastupdated
 * @property int $version
 * @property string $state
 * @property int $stateCreator_id
 * @property string $name
 * @property string $responsible
 * @property string $org
 * @property string $org_mail
 * @property string $protokoll
 * @property string $recht
 * @property string $recht_additional
 * @property string $date_start
 * @property string $date_end
 * @property string $beschreibung
 * @property Expenses[] $expenses
 * @property User $user
 * @property ProjectPost[] $posts
 * @property-read User $creator
 * @property-read int|null $expenses_count
 * @property-read int|null $posts_count
 * @property-read User $stateCreator
 *
 * @method static Builder|Project newModelQuery()
 * @method static Builder|Project newQuery()
 * @method static Builder|Project query()
 * @method static Builder|Project whereBeschreibung($value)
 * @method static Builder|Project whereCreatedat($value)
 * @method static Builder|Project whereCreatorId($value)
 * @method static Builder|Project whereDateEnd($value)
 * @method static Builder|Project whereDateStart($value)
 * @method static Builder|Project whereId($value)
 * @method static Builder|Project whereLastupdated($value)
 * @method static Builder|Project whereName($value)
 * @method static Builder|Project whereOrg($value)
 * @method static Builder|Project whereOrgMail($value)
 * @method static Builder|Project whereProtokoll($value)
 * @method static Builder|Project whereRecht($value)
 * @method static Builder|Project whereRechtAdditional($value)
 * @method static Builder|Project whereResponsible($value)
 * @method static Builder|Project whereState($value)
 * @method static Builder|Project whereStateCreatorId($value)
 * @method static Builder|Project whereVersion($value)
 *
 * @property string|null $org-mail
 * @property string|null $recht-additional
 * @property string|null $date-start
 * @property string|null $date-end
 *
 * @method static \Database\Factories\Legacy\ProjectFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class Project extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'projekte';

    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['creator_id', 'createdat', 'lastupdated', 'version', 'state', 'stateCreator_id', 'name', 'responsible', 'org', 'org-mail', 'protokoll', 'recht', 'recht-additional', 'date-start', 'date-end', 'beschreibung'];

    protected $dispatchesEvents = [
        'updating' => UpdatingModel::class,
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function expenses()
    {
        return $this->hasMany('App\Models\Legacy\Expenses', 'projekt_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo('App\Models\User', 'creator_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function stateCreator()
    {
        return $this->belongsTo('App\Models\User', 'stateCreator_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(ProjectPost::class, 'projekt_id');
    }
}

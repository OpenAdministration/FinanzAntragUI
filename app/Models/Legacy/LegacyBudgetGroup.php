<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Legacy\LegacyBudgetGroup
 *
 * @property int $id
 * @property int $hhp_id
 * @property string $gruppen_name
 * @property int $type
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Legacy\LegacyBudgetItem> $budgetItems
 * @property-read int|null $budget_items_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetGroup whereGruppenName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetGroup whereHhpId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetGroup whereType($value)
 *
 * @mixin \Eloquent
 */
class LegacyBudgetGroup extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'haushaltsgruppen';

    /**
     * @var array
     */
    protected $fillable = ['hhp_id', 'gruppen_name', 'type'];

    public function budgetItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LegacyBudgetItem::class, 'hhpgruppen_id');
    }
}

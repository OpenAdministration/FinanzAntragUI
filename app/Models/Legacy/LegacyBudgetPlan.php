<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Legacy\LegacyBudgetPlan
 *
 * @property int $id
 * @property string $von
 * @property string $bis
 * @property string $state
 * @property LegacyBudgetGroup[] $budgetGroups
 * @property-read int|null $budget_groups_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Legacy\LegacyBudgetItem> $budgetItems
 * @property-read int|null $budget_items_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetPlan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetPlan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetPlan query()
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetPlan whereBis($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetPlan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetPlan whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetPlan whereVon($value)
 *
 * @mixin \Eloquent
 */
class LegacyBudgetPlan extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'haushaltsplan';

    /**
     * @var array
     */
    protected $fillable = ['von', 'bis', 'state'];

    public function budgetGroups(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LegacyBudgetGroup::class, 'hhp_id');
    }

    public function budgetItems(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(LegacyBudgetItem::class, LegacyBudgetGroup::class);
    }
}

<?php

namespace App\Models;

use App\Models\Enums\BudgetPlanState;
use Carbon\Carbon;
use Database\Factories\BudgetPlanFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\BudgetPlan
 *
 * @property int $id
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property Carbon $resolution_date
 * @property Carbon $approval_date
 * @property BudgetPlanState $state
 * @property BudgetPlan $parentPlan
 * @property BudgetItem[] $budgetItems
 * @property int|null $parent_plan_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read int|null $budget_items_count
 *
 * @method static BudgetPlanFactory factory($count = null, $state = [])
 * @method static Builder|BudgetPlan newModelQuery()
 * @method static Builder|BudgetPlan newQuery()
 * @method static Builder|BudgetPlan query()
 *
 * @mixin Eloquent
 */
class BudgetPlan extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'budget_plan';

    /**
     * @var array
     */
    protected $fillable = ['resolution_date', 'approval_date', 'state', 'parent_plan'];

    protected function casts(): array
    {
        return [
            'state' => BudgetPlanState::class,
            'resolution_date' => 'date',
            'approval_date' => 'date',
        ];
    }

    public function budgetItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }

    public function fiscalYear(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }
}

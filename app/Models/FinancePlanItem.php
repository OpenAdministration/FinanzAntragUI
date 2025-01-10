<?php

namespace App\Models;

use Database\Factories\FinancePlanItemFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\FinancePlanItem
 *
 * @property int $id
 * @property int $topic_id
 * @property string $name
 * @property string $value
 * @property int $amount
 * @property string $total
 * @property string $description
 *
 * @method static FinancePlanItemFactory factory($count = null, $state = [])
 * @method static Builder|FinancePlanItem newModelQuery()
 * @method static Builder|FinancePlanItem newQuery()
 * @method static Builder|FinancePlanItem query()
 * @method static Builder|FinancePlanItem whereAmount($value)
 * @method static Builder|FinancePlanItem whereDescription($value)
 * @method static Builder|FinancePlanItem whereId($value)
 * @method static Builder|FinancePlanItem whereName($value)
 * @method static Builder|FinancePlanItem whereTopicId($value)
 * @method static Builder|FinancePlanItem whereTotal($value)
 * @method static Builder|FinancePlanItem whereValue($value)
 *
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Application|null $application
 * @property-read FinancePlanTopic|null $financePlanTopic
 *
 * @method static Builder|FinancePlanItem whereCreatedAt($value)
 * @method static Builder|FinancePlanItem whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class FinancePlanItem extends Model
{
    use HasFactory;

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function financePlanTopic(): BelongsTo
    {
        return $this->belongsTo(FinancePlanTopic::class);
    }
}

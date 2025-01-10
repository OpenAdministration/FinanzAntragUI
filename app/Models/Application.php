<?php

namespace App\Models;

use Database\Factories\ApplicationFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\Application
 *
 * @property int $id
 * @property int $user_id
 * @property int $project_id
 * @property string $state
 * @property string $form_name
 * @property string $form_version
 * @property int $version
 * @property string $legal_basis
 * @property string $legal_basis_details
 * @property string $constraints
 * @property string $funding_total
 * @property mixed $extra_fields
 *
 * @method static ApplicationFactory factory($count = null, $state = [])
 * @method static Builder|Application newModelQuery()
 * @method static Builder|Application newQuery()
 * @method static Builder|Application query()
 * @method static Builder|Application whereConstraints($value)
 * @method static Builder|Application whereExtraFields($value)
 * @method static Builder|Application whereFormName($value)
 * @method static Builder|Application whereFormVersion($value)
 * @method static Builder|Application whereFundingTotal($value)
 * @method static Builder|Application whereId($value)
 * @method static Builder|Application whereLegalBasis($value)
 * @method static Builder|Application whereLegalBasisDetails($value)
 * @method static Builder|Application whereProjectId($value)
 * @method static Builder|Application whereState($value)
 * @method static Builder|Application whereUserId($value)
 * @method static Builder|Application whereVersion($value)
 *
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ApplicationAttachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read Collection<int, FinancePlanItem> $financePlanItems
 * @property-read int|null $finance_plan_items_count
 * @property-read Collection<int, FinancePlanTopic> $financePlanTopics
 * @property-read int|null $finance_plan_topics_count
 * @property-read Project $project
 *
 * @method static Builder|Application whereCreatedAt($value)
 * @method static Builder|Application whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class Application extends Model
{
    use HasFactory;

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function financePlanTopics(): HasMany
    {
        return $this->hasMany(FinancePlanTopic::class);
    }

    public function financePlanItems(): HasMany
    {
        return $this->hasMany(FinancePlanItem::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ApplicationAttachment::class);
    }
}

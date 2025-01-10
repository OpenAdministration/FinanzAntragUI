<?php

namespace App\Models\Legacy;

use App\Models\BudgetItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Legacy\Booking
 *
 * @property int $id
 * @property int $titel_id
 * @property int $user_id
 * @property int $kostenstelle
 * @property int $zahlung_id
 * @property int $zahlung_type
 * @property int $beleg_id
 * @property string $beleg_type
 * @property string $timestamp
 * @property string $comment
 * @property float $value
 * @property int $canceled
 * @property BudgetItem $haushaltstitel
 * @property User $user
 * @property-read BudgetItem $budgetItem
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Booking newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Booking newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Booking query()
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereBelegId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereBelegType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereCanceled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereKostenstelle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereTimestamp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereTitelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereZahlungId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereZahlungType($value)
 *
 * @mixin \Eloquent
 */
class Booking extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'booking';

    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['titel_id', 'user_id', 'kostenstelle', 'zahlung_id', 'zahlung_type', 'beleg_id', 'beleg_type', 'timestamp', 'comment', 'value', 'canceled'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function budgetItem()
    {
        return $this->belongsTo(BudgetItem::class, 'titel_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}
